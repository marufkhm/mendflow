<?php
// Sprint 10: запрос ссылки для сброса пароля
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once 'auth_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

if (!checkRateLimit('forgot_password', 3, 3600)) {
    http_response_code(429);
    echo json_encode(['error' => 'Слишком много запросов. Попробуйте через час.']);
    exit;
}

try {
    $data  = json_decode(file_get_contents('php://input'), true);
    $email = trim(strtolower($data['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный формат email']);
        exit;
    }

    requestPasswordReset($pdo, $email);

    // Всегда одинаковый ответ — не раскрываем, есть ли аккаунт
    echo json_encode([
        'success' => true,
        'message' => 'Если аккаунт с таким email существует, мы отправили ссылку для сброса пароля.',
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера']);
}
