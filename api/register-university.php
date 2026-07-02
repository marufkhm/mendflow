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
    $data           = json_decode(file_get_contents('php://input'), true);
    $universityName = trim($data['universityName'] ?? '');
    $email          = trim(strtolower($data['email'] ?? ''));
    $password       = $data['password'] ?? '';
    $city           = trim($data['city'] ?? '');
    $countryName    = trim($data['countryName'] ?? '');
    $website        = trim($data['website'] ?? '');
    $description    = trim($data['description'] ?? '');

    if (!$universityName || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Название университета, email и пароль обязательны']);
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
        if (empty($existing['email_verified_at'])) {
            $verify = createAndSendVerification($pdo, (int)$existing['id'], $email, $universityName);
            echo json_encode(array_merge([
                'success'               => true,
                'requires_verification' => true,
                'email_verified'        => false,
                'email'                 => $email,
                'message'               => verificationSuccessMessage($verify),
            ], verificationApiFields($verify)));
            exit;
        }
        http_response_code(409);
        echo json_encode(['error' => 'Этот email уже зарегистрирован']);
        exit;
    }

    $pdo->beginTransaction();

    $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role, email_verified_at) VALUES (?, '', ?, ?, 'university', NULL)")
        ->execute([$universityName, $email, password_hash($password, PASSWORD_DEFAULT)]);

    $userId = (int)$pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO university_profiles (user_id, university_name, city, country_name, website, description, verified)
        VALUES (?, ?, ?, ?, ?, ?, 0)
    ")->execute([$userId, $universityName, $city ?: null, $countryName ?: null, $website ?: null, $description ?: null]);

    $pdo->commit();

    $verify = createAndSendVerification($pdo, $userId, $email, $universityName);
    echo json_encode(array_merge([
        'success'               => true,
        'requires_verification' => true,
        'email_verified'        => false,
        'email'                 => $email,
        'message'               => 'Аккаунт университета создан. ' . verificationSuccessMessage($verify),
    ], verificationApiFields($verify)));

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера при регистрации']);
}
