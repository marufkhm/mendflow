<?php
// Sprint 10: повторная отправка кода подтверждения
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once 'auth_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

if (!checkRateLimit('resend_verify', 3, 900)) {
    http_response_code(429);
    echo json_encode(['error' => 'Слишком много запросов. Подождите 15 минут.']);
    exit;
}

try {
    $data  = json_decode(file_get_contents('php://input'), true);
    $email = trim(strtolower($data['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный email']);
        exit;
    }

    $st = $pdo->prepare("SELECT id, first_name, last_name, email_verified_at FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => true, 'message' => 'Если email зарегистрирован, код отправлен.']);
        exit;
    }

    if (!empty($user['email_verified_at'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email уже подтверждён. Можно войти.']);
        exit;
    }

    $result = createAndSendVerification($pdo, (int)$user['id'], $email, getUserDisplayName($user));

    echo json_encode(array_merge([
        'success' => true,
        'message' => verificationSuccessMessage($result),
        'expires_in' => $result['expires_in'],
    ], verificationApiFields($result)));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера']);
}
