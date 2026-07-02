<?php
// Sprint 9: rate limiting | Sprint 10: блокировка входа без подтверждённого email
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once 'auth_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

if (!checkRateLimit('login', 5, 900)) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Слишком много попыток входа. Подождите 15 минут и попробуйте снова.',
        'retry_after' => 900,
    ]);
    exit;
}

try {
    $data     = json_decode(file_get_contents('php://input'), true);
    $email    = trim(strtolower($data['email'] ?? ''));
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Email и пароль обязательны']);
        exit;
    }

    ensureAuthEmailSchema($pdo);

    $stmt = $pdo->prepare("SELECT id, first_name, last_name, password_hash, email_verified_at FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Неверный email или пароль']);
        exit;
    }

    // Подтверждение email временно отключено — верифицируем автоматически
    if (empty($user['email_verified_at'])) {
        $pdo->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
    }

    echo json_encode([
        'success' => true,
        'token'   => generateToken($user['id']),
        'user'    => getUser($user['id']),
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера при входе']);
}
