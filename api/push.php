<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

function pushJson($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'public_key';
    if ($action === 'public_key') {
        $key = env('VAPID_PUBLIC_KEY');
        if (!$key || !preg_match('/^[A-Za-z0-9_-]+$/', $key)) {
            pushJson(['error' => 'Push не настроен (VAPID_PUBLIC_KEY)'], 503);
        }
        pushJson(['publicKey' => $key]);
    }
    pushJson(['error' => 'Неизвестное действие'], 400);
}

if ($method !== 'POST') {
    pushJson(['error' => 'Метод не разрешён'], 405);
}

try {
    require_once __DIR__ . '/push_helper.php';

    $userId = verifyToken();
    ensurePushTables($pdo);

    $data   = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';

    if ($action === 'subscribe') {
        $sub = $data['subscription'] ?? [];
        $endpoint = trim($sub['endpoint'] ?? '');
        $p256dh   = trim($sub['keys']['p256dh'] ?? '');
        $auth     = trim($sub['keys']['auth'] ?? '');

        if (!$endpoint || !$p256dh || !$auth) {
            pushJson(['error' => 'Некорректная подписка'], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth)
        ");
        $stmt->execute([$userId, $endpoint, $p256dh, $auth]);

        pushJson(['success' => true]);
    }

    if ($action === 'unsubscribe') {
        $endpoint = trim($data['endpoint'] ?? '');
        if ($endpoint) {
            $pdo->prepare('DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?')
                ->execute([$userId, $endpoint]);
        } else {
            $pdo->prepare('DELETE FROM push_subscriptions WHERE user_id = ?')->execute([$userId]);
        }
        pushJson(['success' => true]);
    }

    pushJson(['error' => 'Неизвестное действие'], 400);
} catch (Throwable $e) {
    pushJson(['error' => 'Внутренняя ошибка сервера'], 500);
}
