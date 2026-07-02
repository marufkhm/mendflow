<?php
// Sprint 9: rate limiting | Sprint 10: email verification
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once 'auth_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

if (!checkRateLimit('register', 3, 3600)) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Слишком много попыток регистрации. Попробуйте через час.',
        'retry_after' => 3600,
    ]);
    exit;
}

try {
    $data      = json_decode(file_get_contents('php://input'), true);
    $firstName = trim($data['firstName'] ?? '');
    $lastName  = trim($data['lastName']  ?? '');
    $email     = trim(strtolower($data['email'] ?? ''));
    $password  = $data['password']       ?? '';

    if (!$firstName || !$lastName || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Заполните все поля']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный формат email']);
        exit;
    }
    $pwErr = validatePasswordStrength($password);
    if ($pwErr) {
        http_response_code(400);
        echo json_encode(['error' => $pwErr]);
        exit;
    }

    ensureAuthEmailSchema($pdo);

    $check = $pdo->prepare("SELECT id, email_verified_at FROM users WHERE email = ? LIMIT 1");
    $check->execute([$email]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        http_response_code(409);
        echo json_encode(['error' => 'Этот email уже зарегистрирован']);
        exit;
    }

    $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, email_verified_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT)]);

    $userId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'token'   => generateToken($userId),
        'user'    => getUser($userId),
        'message' => 'Аккаунт создан',
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера при регистрации']);
}
