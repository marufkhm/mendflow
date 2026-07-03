<?php
/**
 * auth_email.php — email verification & password reset helpers
 */

require_once __DIR__ . '/mail.php';

function ensureAuthEmailSchema(PDO $pdo): void {
    static $done = false;
    if ($done) return;

    try {
        $cols = $pdo->query("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        ")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('email_verified_at', $cols, true)) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL");
            } catch (Throwable $e) {}
            // Только для уже существовавших аккаунтов до включения верификации
            try {
                $pdo->exec("UPDATE users SET email_verified_at = COALESCE(created_at, NOW()) WHERE email_verified_at IS NULL");
            } catch (Throwable $e) {}
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS email_verifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            code VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ev_email (email),
            INDEX idx_ev_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_pr_token (token),
            INDEX idx_pr_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}

    $done = true;
}

function isUserEmailVerified(PDO $pdo, int $userId): bool {
    ensureAuthEmailSchema($pdo);
    try {
        $st = $pdo->prepare("SELECT email_verified_at FROM users WHERE id = ? LIMIT 1");
        $st->execute([$userId]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return true;
    }
}

function generateVerificationCode(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function createAndSendVerification(PDO $pdo, int $userId, string $email, string $displayName = ''): array {
    ensureAuthEmailSchema($pdo);

    $code    = generateVerificationCode();
    $expires = date('Y-m-d H:i:s', time() + 15 * 60);

    $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ? OR email = ?")
        ->execute([$userId, $email]);
    $pdo->prepare("INSERT INTO email_verifications (user_id, email, code, expires_at) VALUES (?, ?, ?, ?)")
        ->execute([$userId, $email, $code, $expires]);

    // Если нет Resend/SMTP — на shared hosting mail() ненадёжна (может висеть/падать),
    // поэтому пропускаем отправку и показываем код на экране.
    $hasRealMailer = (bool)(env('RESEND_API_KEY') || env('SMTP_HOST'));
    $usePhpMail    = env('MAIL_USE_PHP_MAIL', '0') === '1';

    $mailResult = ['ok' => false, 'method' => 'skipped', 'error' => null];
    if ($hasRealMailer || $usePhpMail) {
        $name = $displayName ?: 'пользователь';
        $html = emailTemplate(
            'Подтверждение email',
            "<p>Здравствуйте, <strong>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
             <p>Ваш код подтверждения для Mendflow:</p>
             <div style='font-size:32px;font-weight:700;letter-spacing:8px;margin:20px 0;color:#1a1523'>{$code}</div>
             <p>Код действует 15 минут.</p>"
        );
        try {
            $mailResult = sendEmail($email, 'Код подтверждения Mendflow', $html);
        } catch (Throwable $e) {
            $mailResult = ['ok' => false, 'method' => 'error', 'error' => $e->getMessage()];
        }
    }
    $sent = $mailResult['ok'];
    $showCode = env('MAIL_SHOW_CODE', '0') === '1'
        || env('APP_ENV', 'production') !== 'production'
        || (!$sent && !$hasRealMailer);

    $result = [
        'sent'        => $sent,
        'email_sent'  => $sent && $hasRealMailer,
        'expires_in'  => 900,
        'mail_method' => $mailResult['method'] ?? null,
    ];

    if ($showCode) {
        $result['verification_code'] = $code;
        $result['dev_code'] = $code;
    }

    if (!$sent && !empty($mailResult['error'])) {
        $result['mail_error'] = $mailResult['error'];
    }

    return $result;
}

function verifyEmailCode(PDO $pdo, string $email, string $code): ?int {
    ensureAuthEmailSchema($pdo);
    $code = preg_replace('/\D/', '', $code);
    if (strlen($code) !== 6) return null;

    $st = $pdo->prepare("
        SELECT ev.user_id
        FROM email_verifications ev
        JOIN users u ON u.id = ev.user_id
        WHERE ev.email = ? AND ev.code = ? AND ev.expires_at > NOW()
        LIMIT 1
    ");
    $st->execute([$email, $code]);
    $userId = $st->fetchColumn();
    if (!$userId) return null;

    $userId = (int)$userId;
    $pdo->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$userId]);
    return $userId;
}

function mailerEnabled(): bool {
    return (bool)(env('RESEND_API_KEY') || env('SMTP_HOST') || env('MAIL_USE_PHP_MAIL', '0') === '1');
}

function sendEmailSafe(string $to, string $subject, string $html): void {
    if (!mailerEnabled()) return;
    try {
        sendEmail($to, $subject, $html);
    } catch (Throwable $e) {
        // не роняем основной запрос из-за проблем с почтой
    }
}

function sendWelcomeEmailMessage(string $email, string $displayName): void {
    $appUrl = rtrim(env('APP_URL', ''), '/');
    $html = emailTemplate(
        'Добро пожаловать в Mendflow!',
        "<p>Здравствуйте, <strong>" . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
         <p>Ваш email подтверждён. Теперь вы можете войти и пользоваться платформой.</p>"
         . ($appUrl ? "<p><a href=\"{$appUrl}\" style='color:#6366f1'>Открыть Mendflow</a></p>" : '')
    );
    sendEmailSafe($email, 'Добро пожаловать в Mendflow', $html);
}

function requestPasswordReset(PDO $pdo, string $email): bool {
    ensureAuthEmailSchema($pdo);
    $email = trim(strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

    $st = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch(PDO::FETCH_ASSOC);
    if (!$user) return true; // Не раскрываем, есть ли email

    $userId = (int)$user['id'];
    $token  = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")
        ->execute([$userId]);
    $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)")
        ->execute([$userId, $token, $expires]);

    $appUrl = rtrim(env('APP_URL', ''), '/');
    $link   = $appUrl ? "{$appUrl}/?reset={$token}" : "?reset={$token}";
    $name   = $user['first_name'] ?: 'пользователь';

    $html = emailTemplate(
        'Сброс пароля',
        "<p>Здравствуйте, <strong>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
         <p>Вы запросили сброс пароля. Нажмите на кнопку ниже — ссылка действует 1 час.</p>
         <p style='margin:24px 0'><a href=\"{$link}\" style='display:inline-block;padding:12px 24px;background:#6366f1;color:#fff;text-decoration:none;border-radius:10px;font-weight:600'>Сбросить пароль</a></p>
         <p style='font-size:13px;color:#9ca3af'>Или скопируйте ссылку:<br>{$link}</p>"
    );
    sendEmailSafe($email, 'Сброс пароля Mendflow', $html);

    if (env('APP_ENV', 'production') !== 'production') {
        error_log("[Mendflow dev] Password reset link for {$email}: {$link}");
    }
    return true;
}

function resetPasswordWithToken(PDO $pdo, string $token, string $password): array {
    ensureAuthEmailSchema($pdo);

    if ($err = validatePasswordStrength($password)) {
        return ['error' => $err];
    }

    $st = $pdo->prepare("
        SELECT pr.user_id
        FROM password_resets pr
        WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used_at IS NULL
        LIMIT 1
    ");
    $st->execute([trim($token)]);
    $userId = $st->fetchColumn();
    if (!$userId) {
        return ['error' => 'Ссылка недействительна или истекла. Запросите сброс пароля снова.'];
    }

    $userId = (int)$userId;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $userId]);
    $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?")->execute([trim($token)]);
    $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$userId]);

    return ['success' => true, 'user_id' => $userId];
}

function getUserDisplayName(array $user): string {
    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    return $name ?: ($user['email'] ?? 'пользователь');
}

/** Поля для JSON-ответа API после createAndSendVerification */
function verificationApiFields(array $verify): array {
    $fields = [
        'email_sent' => !empty($verify['email_sent']),
        'sent'       => !empty($verify['sent']),
    ];
    if (!empty($verify['verification_code'])) {
        $fields['verification_code'] = $verify['verification_code'];
        $fields['dev_code']         = $verify['verification_code'];
    }
    if (!empty($verify['mail_error'])) {
        $fields['mail_error'] = $verify['mail_error'];
    }
    return $fields;
}

function verificationSuccessMessage(array $verify): string {
    if (!empty($verify['email_sent'])) {
        return 'Код подтверждения отправлен на email. Проверьте также папку «Спам».';
    }
    if (!empty($verify['verification_code'])) {
        return 'Код подтверждения показан на экране (почта на сервере не настроена).';
    }
    return 'Не удалось отправить код. Нажмите «Отправить снова».';
}
