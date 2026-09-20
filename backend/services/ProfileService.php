<?php

declare(strict_types=1);

final class ProfileService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function update(array $payload): array
    {
        $email = SessionGuard::requireAuthenticated();
        $user = $this->findUserByEmail($email);
        $userId = (int) $user['id'];

        $organization = $this->nullableString($payload['organization'] ?? null, 190);
        $specialty = $this->nullableString($payload['specialty'] ?? null, 120);
        $education = $this->nullableString($payload['education'] ?? null, 50);
        $countryCode = $this->nullableString($payload['countryCode'] ?? null, 12);
        $countryName = $this->nullableString($payload['countryName'] ?? null, 120);
        $styleFont = $this->sanitizeFontTheme($payload['style']['fontTheme'] ?? null);
        $styleAccent = $this->sanitizeColor($payload['style']['accentColor'] ?? null);
        $styleCard = $this->sanitizeColor($payload['style']['cardColor'] ?? null);
        $interests = $this->sanitizeInterests($payload['interests'] ?? []);

        $this->ensureProfileRow($userId);

        $stmt = $this->pdo->prepare('
            UPDATE profiles
            SET
                organization = :organization,
                specialty = :specialty,
                education_level = :education_level,
                country_code = :country_code,
                country_name = :country_name,
                style_font = :style_font,
                style_accent_color = :style_accent_color,
                style_card_color = :style_card_color
            WHERE user_id = :user_id
        ');
        $stmt->execute([
            'organization' => $organization,
            'specialty' => $specialty,
            'education_level' => $education,
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'style_font' => $styleFont,
            'style_accent_color' => $styleAccent,
            'style_card_color' => $styleCard,
            'user_id' => $userId,
        ]);

        $deleteInterests = $this->pdo->prepare('DELETE FROM profile_interests WHERE user_id = :user_id');
        $deleteInterests->execute(['user_id' => $userId]);

        if ($interests !== []) {
            $insertInterest = $this->pdo->prepare('
                INSERT INTO profile_interests (user_id, interest_slug)
                VALUES (:user_id, :interest_slug)
            ');

            foreach ($interests as $interest) {
                $insertInterest->execute([
                    'user_id' => $userId,
                    'interest_slug' => $interest,
                ]);
            }
        }

        return [
            'ok' => true,
            'message' => 'Профиль обновлен.',
            'profile' => $this->get($userId)['profile'],
        ];
    }

    public function get(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                u.id,
                u.email,
                u.first_name,
                u.last_name,
                p.organization,
                p.specialty,
                p.education_level,
                p.country_code,
                p.country_name,
                p.avatar_path,
                p.style_font,
                p.style_accent_color,
                p.style_card_color
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.id = :user_id
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Профиль не найден.');
        }

        $interestStmt = $this->pdo->prepare('
            SELECT interest_slug
            FROM profile_interests
            WHERE user_id = :user_id
            ORDER BY id ASC
        ');
        $interestStmt->execute(['user_id' => $userId]);
        $interests = array_map(
            static fn (array $interestRow): string => (string) $interestRow['interest_slug'],
            $interestStmt->fetchAll()
        );

        return [
            'ok' => true,
            'profile' => $this->mapProfile($row, $interests, true),
        ];
    }

    public function getCurrent(): array
    {
        $email = SessionGuard::requireAuthenticated();
        $user = $this->findUserByEmail($email);

        return $this->get((int) $user['id']);
    }

    public function getPublic(string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Укажи корректный email.');
        }

        $user = $this->findUserByEmail(strtolower(trim($email)));

        $stmt = $this->pdo->prepare('
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                p.organization,
                p.specialty,
                p.education_level,
                p.country_code,
                p.country_name,
                p.avatar_path,
                p.style_font,
                p.style_accent_color,
                p.style_card_color
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.id = :user_id
            LIMIT 1
        ');
        $stmt->execute(['user_id' => (int) $user['id']]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Профиль не найден.');
        }

        $interestStmt = $this->pdo->prepare('
            SELECT interest_slug
            FROM profile_interests
            WHERE user_id = :user_id
            ORDER BY id ASC
        ');
        $interestStmt->execute(['user_id' => (int) $user['id']]);
        $interests = array_map(
            static fn (array $interestRow): string => (string) $interestRow['interest_slug'],
            $interestStmt->fetchAll()
        );

        return [
            'ok' => true,
            'profile' => $this->mapProfile($row, $interests, false),
        ];
    }

    public function uploadAvatar(array $file): array
    {
        $email = SessionGuard::requireAuthenticated();
        $user = $this->findUserByEmail($email);
        $userId = (int) $user['id'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Выбери изображение для аватара.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $tmpName !== '' ? (string) $finfo->file($tmpName) : '';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            throw new RuntimeException('Для аватара подходят только JPG, PNG или GIF.');
        }

        $storedPath = UploadStorage::saveUploadedFile($file, 'avatars');
        $this->ensureProfileRow($userId);

        $stmt = $this->pdo->prepare('
            UPDATE profiles
            SET avatar_path = :avatar_path
            WHERE user_id = :user_id
        ');
        $stmt->execute([
            'avatar_path' => $storedPath,
            'user_id' => $userId,
        ]);

        return [
            'ok' => true,
            'message' => 'Аватар обновлен.',
            'avatarPath' => $storedPath,
            'profile' => $this->get($userId)['profile'],
        ];
    }

    public function createEmptyProfile(int $userId): void
    {
        $this->ensureProfileRow($userId);
    }

    private function ensureProfileRow(int $userId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO profiles (user_id)
            VALUES (:user_id)
            ON DUPLICATE KEY UPDATE user_id = user_id
        ');
        $stmt->execute(['user_id' => $userId]);
    }

    private function findUserByEmail(string $email): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, email, first_name, last_name
            FROM users
            WHERE email = :email
            LIMIT 1
        ');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException('Пользователь не найден.');
        }

        return $user;
    }

    private function mapProfile(array $row, array $interests, bool $includeEmail): array
    {
        $countryCode = (string) ($row['country_code'] ?? '');
        $countryName = (string) ($row['country_name'] ?? '');

        $profile = [
            'userId' => (int) ($row['id'] ?? 0),
            'firstName' => (string) ($row['first_name'] ?? ''),
            'lastName' => (string) ($row['last_name'] ?? ''),
            'organization' => (string) ($row['organization'] ?? ''),
            'specialty' => (string) ($row['specialty'] ?? ''),
            'education' => (string) ($row['education_level'] ?? ''),
            'countryCode' => $countryCode,
            'countryName' => $countryName,
            'countryValue' => $countryCode !== '' || $countryName !== '' ? $countryCode . '|' . $countryName : '',
            'photo' => (string) ($row['avatar_path'] ?? ''),
            'interests' => array_values(array_slice($interests, 0, 3)),
            'style' => [
                'fontTheme' => (string) ($row['style_font'] ?? 'manrope') ?: 'manrope',
                'accentColor' => (string) ($row['style_accent_color'] ?? '#e78479') ?: '#e78479',
                'cardColor' => (string) ($row['style_card_color'] ?? '#f1e4d0') ?: '#f1e4d0',
            ],
        ];

        if ($includeEmail) {
            $profile['email'] = (string) ($row['email'] ?? '');
        }

        return $profile;
    }

    private function sanitizeInterests(mixed $interests): array
    {
        if (!is_array($interests)) {
            return [];
        }

        $allowed = ['design', 'frontend', 'ai', 'product', 'data'];
        $values = [];

        foreach ($interests as $interest) {
            $value = strtolower(trim((string) $interest));
            if ($value === '' || !in_array($value, $allowed, true) || in_array($value, $values, true)) {
                continue;
            }
            $values[] = $value;
            if (count($values) >= 3) {
                break;
            }
        }

        return $values;
    }

    private function sanitizeFontTheme(mixed $fontTheme): string
    {
        $value = strtolower(trim((string) $fontTheme));
        $allowed = ['manrope', 'fraunces', 'mono'];
        return in_array($value, $allowed, true) ? $value : 'manrope';
    }

    private function sanitizeColor(mixed $color): string
    {
        $value = trim((string) $color);
        if ($value === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return '';
        }
        return strtolower($value);
    }

    private function nullableString(mixed $value, int $maxLength): ?string
    {
        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }
        return mb_substr($string, 0, $maxLength);
    }
}
