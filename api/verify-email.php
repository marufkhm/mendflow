<?php
// Sprint 10: подтверждение email по 6-значному коду
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once 'auth_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

if (!checkRateLimit('verify_email', 10, 900)) {
    http_response_code(429);
    echo json_encode(['error' => 'Слишком много попыток. Подождите 15 минут.']);
    exit;
}

try {
    $data  = json_decode(file_get_contents('php://input'), true);
    $email = trim(strtolower($data['email'] ?? ''));
    $code  = trim($data['code'] ?? '');

    if (!$email || !$code) {
        http_response_code(400);
        echo json_encode(['error' => 'Укажите email и код']);
        exit;
    }

    $userId = verifyEmailCode($pdo, $email, $code);
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный или просроченный код']);
        exit;
    }

    $user = getUser($userId);
    sendWelcomeEmailMessage($email, getUserDisplayName($user ?: ['email' => $email]));

    echo json_encode([
        'success'        => true,
        'email_verified' => true,
        'token'          => generateToken($userId),
        'user'           => $user,
        'message'        => 'Email подтверждён!',
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера']);
}
