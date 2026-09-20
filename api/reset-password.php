<?php
// Sprint 10: установка нового пароля по токену из письма
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once 'auth_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

if (!checkRateLimit('reset_password', 5, 900)) {
    http_response_code(429);
    echo json_encode(['error' => 'Слишком много попыток. Подождите 15 минут.']);
    exit;
}

try {
    $data     = json_decode(file_get_contents('php://input'), true);
    $token    = trim($data['token'] ?? '');
    $password = $data['password'] ?? '';

    if (!$token || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Укажите токен и новый пароль']);
        exit;
    }

    $result = resetPasswordWithToken($pdo, $token, $password);
    if (!empty($result['error'])) {
        http_response_code(400);
        echo json_encode(['error' => $result['error']]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Пароль обновлён. Теперь можно войти.',
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера']);
}
