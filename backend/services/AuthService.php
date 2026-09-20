<?php

declare(strict_types=1);

final class AuthService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function register(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $firstName = trim((string) ($payload['firstName'] ?? ''));
        $lastName = trim((string) ($payload['lastName'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Введите корректный email.');
        }

        if (strlen($password) < 6) {
            throw new RuntimeException('Пароль должен быть не короче 6 символов.');
        }

        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Аккаунт с таким email уже существует.');
        }

        $insert = $this->pdo->prepare('
            INSERT INTO users (
                email,
                password_hash,
                first_name,
                last_name,
                is_verified,
                verification_token,
                token_expiry,
                verified_at
            ) VALUES (
                :email,
                :password_hash,
                :first_name,
                :last_name,
                1,
                NULL,
                NULL,
                NOW()
            )
        ');
        $insert->execute([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        $userId = (int) $this->pdo->lastInsertId();
        $profileService = new ProfileService();
        $profileService->createEmptyProfile($userId);

        return [
            'ok' => true,
            'message' => 'Регистрация завершена.',
            'user' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'profile' => $profileService->get($userId)['profile'],
            ],
        ];
    }

    public function login(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            throw new RuntimeException('Неверный email или пароль.');
        }

        $_SESSION['pwe_user_email'] = $email;
        $profileService = new ProfileService();
        $profile = $profileService->get((int) $user['id'])['profile'];

        return [
            'ok' => true,
            'message' => 'Login successful.',
            'email' => $email,
            'user' => [
                'firstName' => $user['first_name'],
                'lastName' => $user['last_name'],
                'email' => $user['email'],
                'profile' => $profile,
            ],
        ];
    }

    public function stats(): array
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM users');
        $stmt->execute();
        $row = $stmt->fetch();

        return [
            'ok' => true,
            'registeredUsers' => (int) ($row['total'] ?? 0),
        ];
    }

    public function listUsers(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                users.id,
                email,
                first_name,
                last_name,
                p.organization,
                p.specialty,
                p.education_level,
                p.country_code,
                p.country_name,
                p.avatar_path,
                p.style_font,
                p.style_accent_color,
                p.style_card_color,
                created_at
            FROM users
            LEFT JOIN profiles p ON p.user_id = users.id
            ORDER BY created_at DESC
        ');
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $interestStmt = $this->pdo->prepare('
            SELECT user_id, interest_slug
            FROM profile_interests
            ORDER BY id ASC
        ');
        $interestStmt->execute();

        $interestMap = [];
        foreach ($interestStmt->fetchAll() as $interestRow) {
            $userId = (int) ($interestRow['user_id'] ?? 0);
            $slug = (string) ($interestRow['interest_slug'] ?? '');
            if ($userId <= 0 || $slug === '') {
                continue;
            }
            $interestMap[$userId] ??= [];
            if (!in_array($slug, $interestMap[$userId], true) && count($interestMap[$userId]) < 3) {
                $interestMap[$userId][] = $slug;
            }
        }

        $users = array_map(static function (array $user) use ($interestMap): array {
            $userId = (int) ($user['id'] ?? 0);
            return [
                'email' => (string) $user['email'],
                'firstName' => (string) $user['first_name'],
                'lastName' => (string) $user['last_name'],
                'organization' => (string) ($user['organization'] ?? ''),
                'specialty' => (string) ($user['specialty'] ?? ''),
                'education' => (string) ($user['education_level'] ?? ''),
                'countryCode' => (string) ($user['country_code'] ?? ''),
                'countryName' => (string) ($user['country_name'] ?? ''),
                'photo' => (string) ($user['avatar_path'] ?? ''),
                'style' => [
                    'fontTheme' => (string) ($user['style_font'] ?? 'manrope') ?: 'manrope',
                    'accentColor' => (string) ($user['style_accent_color'] ?? '#e78479') ?: '#e78479',
                    'cardColor' => (string) ($user['style_card_color'] ?? '#f1e4d0') ?: '#f1e4d0',
                ],
                'interests' => $interestMap[$userId] ?? [],
                'createdAt' => (string) $user['created_at'],
            ];
        }, $rows);

        return [
            'ok' => true,
            'users' => $users,
        ];
    }
}
