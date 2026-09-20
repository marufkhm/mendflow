<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Heartbeat — обновить last_seen
        $userId = verifyToken();
        $pdo->prepare("UPDATE users SET last_seen = UTC_TIMESTAMP() WHERE id = ?")
            ->execute([$userId]);
        echo json_encode(['ok' => true]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Получить онлайн-статус пользователей
        $ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
        if (empty($ids)) { echo json_encode(['online' => []]); exit; }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
            "SELECT id, last_seen,
                    CASE WHEN last_seen > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE) THEN 1 ELSE 0 END AS is_online
             FROM users WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r['id']] = [
                'is_online' => (bool)$r['is_online'],
                'last_seen' => utcDate($r['last_seen']),
            ];
        }
        echo json_encode(['online' => $result]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
