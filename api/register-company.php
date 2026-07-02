<?php
// Sprint 10: email verification
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
    echo json_encode(['error' => 'Слишком много попыток регистрации. Попробуйте через час.']);
    exit;
}

try {
    $data        = json_decode(file_get_contents('php://input'), true);
    $companyName = trim($data['companyName'] ?? '');
    $email       = trim(strtolower($data['email'] ?? ''));
    $password    = $data['password'] ?? '';
    $industry    = trim($data['industry'] ?? '');
    $website     = trim($data['website'] ?? '');
    $city        = trim($data['city'] ?? '');
    $countryName = trim($data['countryName'] ?? '');
    $description = trim($data['description'] ?? '');

    if (!$companyName || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Название компании, email и пароль обязательны']);
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
    if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный формат сайта']);
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

    $pdo->beginTransaction();

    $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role, email_verified_at) VALUES (?, '', ?, ?, 'company', NOW())")
        ->execute([$companyName, $email, password_hash($password, PASSWORD_DEFAULT)]);

    $userId = (int)$pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO company_profiles (user_id, company_name, industry, website, city, country_name, description, verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
    ")->execute([$userId, $companyName, $industry ?: null, $website ?: null, $city ?: null, $countryName ?: null, $description ?: null]);

    $pdo->commit();

    echo mfJsonEncode([
        'success' => true,
        'token'   => generateToken($userId),
        'user'    => getUser($userId),
        'message' => 'Аккаунт компании создан',
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo mfJsonEncode(['error' => 'Ошибка сервера при регистрации']);
}
