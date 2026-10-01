<?php
// Sprint 9: credentials из .env, CORS из конфига, rate limiting, очистка сессий
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

// ── CORS ──────────────────────────────────────────────────────
// Разрешаем только наш домен. В dev-режиме разрешаем localhost.
$appUrl    = env('APP_URL', '');
$appEnv    = env('APP_ENV', 'production');
$origin    = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = array_filter([$appUrl]);
if ($appEnv === 'development') {
    $allowedOrigins[] = 'http://localhost';
    $allowedOrigins[] = 'http://localhost:3000';
    $allowedOrigins[] = 'http://127.0.0.1';
}
if ($origin && in_array(rtrim($origin, '/'), array_map(fn($o) => rtrim($o, '/'), $allowedOrigins), true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} elseif (!$origin) {
    // Прямой запрос (не кросс-доменный) — разрешаем
    header("Access-Control-Allow-Origin: " . ($appUrl ?: '*'));
} else {
    // Неизвестный origin в prod — отвечаем без CORS заголовка (браузер заблокирует)
    if ($appEnv !== 'production') {
        header("Access-Control-Allow-Origin: {$origin}");
    }
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Подключение к БД ──────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_NAME') . ";charset=utf8mb4",
        env('DB_USER'),
        env('DB_PASS'),
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    date_default_timezone_set('UTC');
    $pdo->exec("SET time_zone = '+00:00'");
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    ensureSessionsSchema($pdo);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к БД']);
    exit;
}

function ensureSessionsSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $sqlWithFk = "CREATE TABLE IF NOT EXISTS sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(512) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_sessions_token (token),
        KEY idx_sessions_user (user_id),
        KEY idx_sessions_expires (expires_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $sqlNoFk = "CREATE TABLE IF NOT EXISTS sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(512) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_sessions_token (token),
        KEY idx_sessions_user (user_id),
        KEY idx_sessions_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    try {
        $pdo->exec($sqlWithFk);
    } catch (Throwable $e) {
        try {
            $pdo->exec($sqlNoFk);
        } catch (Throwable $e2) {
        }
    }
}

/** Запрос идёт напрямую в этот PHP-файл (не через include). */
function mfIsDirectScript(string $file): bool
{
    $script = $_SERVER['SCRIPT_FILENAME'] ?? '';
    if ($script === '') {
        return false;
    }
    if ($script === $file) {
        return true;
    }
    $realScript = @realpath($script);
    $realFile   = @realpath($file);
    return $realScript && $realFile && $realScript === $realFile;
}

function mfJsonEncode($data): string
{
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $json = json_encode($data, $flags);
    if ($json === false) {
        return json_encode(['error' => 'Ошибка кодирования ответа'], JSON_UNESCAPED_UNICODE) ?: '{"error":"encode"}';
    }
    return $json;
}

function mfJsonResponse($data, int $code = 200): void
{
    http_response_code($code);
    echo mfJsonEncode($data);
    exit;
}

// Проверка прямого открытия файла (диагностика)
if (mfIsDirectScript(__FILE__)) {
    mfJsonResponse(['success' => true, 'message' => 'БД подключена', 'db' => env('DB_NAME')]);
}

// ── Токен ─────────────────────────────────────────────────────
function getBearerToken() {
    $raw = '';
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strtolower($k) === 'authorization') { $raw = $v; break; }
        }
    }
    if (!$raw && !empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $raw = 'Bearer ' . trim($_SERVER['HTTP_X_AUTH_TOKEN']);
    }
    if (!$raw) {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'HTTP_AUTH'] as $k) {
            if (!empty($_SERVER[$k])) { $raw = $_SERVER[$k]; break; }
        }
    }
    if ($raw && preg_match('/Bearer\s+(.+)$/i', $raw, $m)) return trim($m[1]);
    if (!empty($_GET['token']))        return trim($_GET['token']);
    if (!empty($_COOKIE['mf_token']))  return trim($_COOKIE['mf_token']);
    return null;
}

function verifyToken() {
    $token = getBearerToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Требуется авторизация. Токен не найден.']);
        exit;
    }
    global $pdo;
    $activeSql = userIsActiveSql('u');
    $stmt = $pdo->prepare("
        SELECT s.user_id
        FROM sessions s
        JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW() AND {$activeSql}
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(401);
        echo json_encode(['error' => 'Токен недействителен или истёк. Войдите заново.']);
        exit;
    }
    return (int)$row['user_id'];
}

function verifyTokenSoft() {
    $token = getBearerToken();
    if (!$token) return null;
    global $pdo;
    $activeSql = userIsActiveSql('u');
    $stmt = $pdo->prepare("
        SELECT s.user_id
        FROM sessions s
        JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW() AND {$activeSql}
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? (int)$row['user_id'] : null;
}

function generateToken($userId) {
    $token = bin2hex(random_bytes(32));
    $exp   = date('Y-m-d H:i:s', strtotime('+30 days'));
    global $pdo;
    $pdo->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)")
        ->execute([$userId, $token, $exp]);
    // Sprint 9: очищаем устаревшие сессии (вероятность 10% — не замедляет каждый логин)
    if (mt_rand(1, 10) === 1) {
        try {
            $pdo->exec("DELETE FROM sessions WHERE expires_at < NOW()");
        } catch (Throwable $e) {}
    }
    return $token;
}

/** Soft-delete columns + helpers for account security */
function ensureUserAccountSchema(): void {
    global $pdo;
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = tableColumns('users');
        if (!in_array('is_deleted', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!in_array('deleted_at', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL');
        }
    } catch (Throwable $e) {}
}

function isUserDeleted(array $user): bool {
    return !empty($user['is_deleted']);
}

function userIsActiveSql(string $alias = 'u'): string {
    $cols = tableColumns('users');
    $parts = [];
    if (in_array('is_deleted', $cols, true)) {
        $parts[] = "({$alias}.is_deleted = 0 OR {$alias}.is_deleted IS NULL)";
    }
    if (in_array('is_banned', $cols, true)) {
        $parts[] = "({$alias}.is_banned = 0 OR {$alias}.is_banned IS NULL)";
    }
    return $parts ? implode(' AND ', $parts) : '1=1';
}

// ── Rate Limiting ─────────────────────────────────────────────
/**
 * Проверяет лимит запросов по IP.
 * Использует таблицу rate_limits (создаётся автоматически).
 *
 * @param string $action   Ключ действия, например 'login', 'register'
 * @param int    $maxHits  Максимально допустимо попыток за $windowSec
 * @param int    $windowSec Окно в секундах
 * @return bool  true = разрешено, false = заблокировано
 */
function checkRateLimit(string $action, int $maxHits, int $windowSec): bool {
    global $pdo;

    // Создаём таблицу если нет
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `rate_limits` (
            `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `ip`         VARCHAR(45)  NOT NULL,
            `action`     VARCHAR(64)  NOT NULL,
            `hit_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_rl_ip_action` (`ip`, `action`, `hit_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        return true; // Если таблицу создать нельзя — не блокируем
    }

    $ip  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip  = trim(explode(',', $ip)[0]); // Берём первый IP из proxy-цепочки
    $now = date('Y-m-d H:i:s');
    $win = date('Y-m-d H:i:s', time() - $windowSec);

    // Считаем попытки в окне
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM rate_limits WHERE ip = ? AND action = ? AND hit_at >= ?"
    );
    $st->execute([$ip, $action, $win]);
    $count = (int)$st->fetchColumn();

    if ($count >= $maxHits) {
        return false; // Заблокировано
    }

    // Записываем попытку
    $pdo->prepare("INSERT INTO rate_limits (ip, action, hit_at) VALUES (?, ?, ?)")
        ->execute([$ip, $action, $now]);

    // Случайная чистка старых записей (вероятность 5%)
    if (mt_rand(1, 20) === 1) {
        try {
            $old = date('Y-m-d H:i:s', time() - max($windowSec, 3600));
            $pdo->prepare("DELETE FROM rate_limits WHERE hit_at < ?")->execute([$old]);
        } catch (Throwable $e) {}
    }

    return true;
}

// ── Вспомогательные функции ───────────────────────────────────
function utcDate($datetime) {
    if (!$datetime) return null;
    try {
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d\TH:i:s\Z');
    } catch (Throwable $e) {
        return $datetime;
    }
}

function tableColumns($table) {
    static $cache = [];
    if (!isset($cache[$table])) {
        global $pdo;
        try {
            $stmt = $pdo->query(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_NAME = " . $pdo->quote($table) . "
                 AND TABLE_SCHEMA = DATABASE()"
            );
            $cache[$table] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {
            $cache[$table] = [];
        }
    }
    return $cache[$table];
}

/**
 * Гарантирует, что таблица `friendships` имеет схему, которую ожидает код:
 * sender_id / receiver_id / status / created_at / updated_at.
 * Чинит старые установки (user1_id / user2_id / confirmed). Idempotent.
 */
function ensureFriendshipsSchema() {
    static $done = false;
    if ($done) return;
    $done = true;

    global $pdo;
    try {
        $exists = $pdo->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'"
        )->fetchColumn();

        if (!$exists) {
            $pdo->exec(
                "CREATE TABLE friendships (
                    id          INT AUTO_INCREMENT PRIMARY KEY,
                    sender_id   INT NOT NULL,
                    receiver_id INT NOT NULL,
                    status      ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'accepted',
                    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_friendship (sender_id, receiver_id),
                    INDEX idx_friendship_sender (sender_id),
                    INDEX idx_friendship_receiver (receiver_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            return;
        }

        $cols = $pdo->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'"
        )->fetchAll(PDO::FETCH_COLUMN);
        $cols = array_map('strval', $cols);
        $has  = function ($c) use ($cols) { return in_array($c, $cols, true); };

        // Уже правильная схема — ничего не делаем
        if ($has('sender_id') && $has('receiver_id') && $has('status')) {
            return;
        }

        if (!$has('sender_id'))   $pdo->exec("ALTER TABLE friendships ADD COLUMN sender_id INT NULL");
        if (!$has('receiver_id')) $pdo->exec("ALTER TABLE friendships ADD COLUMN receiver_id INT NULL");
        if (!$has('status'))      $pdo->exec("ALTER TABLE friendships ADD COLUMN status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'accepted'");
        if (!$has('updated_at'))  $pdo->exec("ALTER TABLE friendships ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        // Перенос данных из старого формата user1_id/user2_id/confirmed
        if ($has('user1_id') && $has('user2_id')) {
            $pdo->exec(
                "UPDATE friendships
                 SET sender_id = COALESCE(sender_id, user1_id),
                     receiver_id = COALESCE(receiver_id, user2_id)
                 WHERE sender_id IS NULL OR receiver_id IS NULL"
            );
            if ($has('confirmed')) {
                $pdo->exec("UPDATE friendships SET status = IF(confirmed = 1, 'accepted', 'pending')");
            }
        }

        $pdo->exec("DELETE FROM friendships WHERE sender_id IS NULL OR receiver_id IS NULL");

        // Уникальный ключ нужен для ON DUPLICATE KEY UPDATE
        $hasUnique = $pdo->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
               AND INDEX_NAME = 'uq_friendship'"
        )->fetchColumn();
        if (!$hasUnique) {
            $pdo->exec(
                "DELETE f1 FROM friendships f1
                 INNER JOIN friendships f2
                 WHERE f1.id > f2.id
                   AND f1.sender_id = f2.sender_id
                   AND f1.receiver_id = f2.receiver_id"
            );
            $pdo->exec("ALTER TABLE friendships ADD UNIQUE KEY uq_friendship (sender_id, receiver_id)");
        }
    } catch (Throwable $e) {
        // best-effort: не роняем запрос, если миграция не удалась
    }
}

/** Проверка: пользователи в друзьях (status = accepted). */
function mfAreFriends(PDO $pdo, int $userA, int $userB): bool {
    if ($userA <= 0 || $userB <= 0 || $userA === $userB) {
        return false;
    }
    try {
        if (function_exists('ensureFriendshipsSchema')) {
            ensureFriendshipsSchema();
        }
        $stmt = $pdo->prepare(
            'SELECT 1 FROM friendships
             WHERE status = "accepted"
               AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
             LIMIT 1'
        );
        $stmt->execute([$userA, $userB, $userB, $userA]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/** Таблица блокировок пользователей. */
function ensureBlocksSchema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    global $pdo;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_blocks (
            blocker_id INT NOT NULL,
            blocked_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (blocker_id, blocked_id),
            KEY idx_blocked (blocked_id),
            KEY idx_blocker (blocker_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
}

/** Таблица жалоб на контент / пользователей. */
function ensureReportsSchema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    global $pdo;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT NOT NULL,
            target_type VARCHAR(32) NOT NULL,
            target_id INT NOT NULL,
            reason VARCHAR(32) NOT NULL,
            comment TEXT NULL,
            status ENUM('new','reviewed','resolved') NOT NULL DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_reports_status (status),
            KEY idx_reports_target (target_type, target_id),
            KEY idx_reports_reporter (reporter_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
}

/** Есть ли блокировка между двумя пользователями (в любую сторону). */
function mfEitherBlocked(PDO $pdo, int $userA, int $userB): bool
{
    if ($userA <= 0 || $userB <= 0 || $userA === $userB) {
        return false;
    }
    ensureBlocksSchema();
    try {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM user_blocks
             WHERE (blocker_id = ? AND blocked_id = ?)
                OR (blocker_id = ? AND blocked_id = ?)
             LIMIT 1'
        );
        $stmt->execute([$userA, $userB, $userB, $userA]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/** ID пользователей, с которыми viewer не должен взаимодействовать (блок в любую сторону). */
function mfBlockedUserIds(PDO $pdo, int $viewerId): array
{
    if ($viewerId <= 0) {
        return [];
    }
    ensureBlocksSchema();
    try {
        $stmt = $pdo->prepare(
            'SELECT blocked_id AS uid FROM user_blocks WHERE blocker_id = ?
             UNION
             SELECT blocker_id AS uid FROM user_blocks WHERE blocked_id = ?'
        );
        $stmt->execute([$viewerId, $viewerId]);
        return array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    } catch (Throwable $e) {
        return [];
    }
}

/** SQL-фрагмент для исключения заблокированных авторов из выборки. */
function mfBlockedFilterSql(PDO $pdo, int $viewerId, string $col = 'p.user_id'): ?string
{
    $ids = mfBlockedUserIds($pdo, $viewerId);
    if (!$ids) {
        return null;
    }
    return $col . ' NOT IN (' . implode(',', array_map('intval', $ids)) . ')';
}

/** Удалить дружбу и заявки между пользователями (при блокировке). */
function mfClearFriendshipBetween(PDO $pdo, int $userA, int $userB): void
{
    if ($userA <= 0 || $userB <= 0 || $userA === $userB) {
        return;
    }
    try {
        if (function_exists('ensureFriendshipsSchema')) {
            ensureFriendshipsSchema();
        }
        $pdo->prepare(
            'DELETE FROM friendships
             WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)'
        )->execute([$userA, $userB, $userB, $userA]);
        $pdo->prepare(
            'DELETE FROM friend_requests
             WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?)'
        )->execute([$userA, $userB, $userB, $userA]);
    } catch (Throwable $e) {}
}

function enrichUserProfile(array $user): array {
    global $pdo;
    if (!$user) return $user;
    $role = $user['role'] ?? 'student';

    if ($role === 'university') {
        try {
            $stmt = $pdo->prepare("
                SELECT id, university_name, logo, city, country_name, website, description, verified
                FROM university_profiles WHERE user_id = ? LIMIT 1
            ");
            $stmt->execute([(int)$user['id']]);
            $profile = $stmt->fetch();
            if ($profile) {
                $user['university_id']   = (int)$profile['id'];
                $user['university_name'] = $profile['university_name'];
                $user['universityName']  = $profile['university_name'];
                $user['verified']        = !empty($profile['verified']);
                if (!empty($profile['logo'])) {
                    $user['logo'] = $profile['logo'];
                    if (empty($user['avatar'])) {
                        $user['avatar'] = $profile['logo'];
                    }
                }
                if (empty(trim($user['first_name'] ?? ''))) {
                    $user['first_name'] = $profile['university_name'];
                }
            }
        } catch (Throwable $e) {}
    }

    if ($role === 'company') {
        try {
            $stmt = $pdo->prepare("
                SELECT id, company_name, logo, verified
                FROM company_profiles WHERE user_id = ? LIMIT 1
            ");
            $stmt->execute([(int)$user['id']]);
            $profile = $stmt->fetch();
            if ($profile) {
                $user['company_id']   = (int)$profile['id'];
                $user['company_name'] = $profile['company_name'];
                $user['companyName']  = $profile['company_name'];
                $user['verified']     = !empty($profile['verified']);
                if (!empty($profile['logo'])) {
                    $user['logo'] = $profile['logo'];
                    if (empty($user['avatar'])) {
                        $user['avatar'] = $profile['logo'];
                    }
                }
                if (empty(trim($user['first_name'] ?? ''))) {
                    $user['first_name'] = $profile['company_name'];
                }
            }
        } catch (Throwable $e) {}
    }

    foreach (['avatar', 'logo', 'cover_image', 'photo'] as $mediaKey) {
        if (!empty($user[$mediaKey])) {
            $user[$mediaKey] = normalizeMediaUrl((string)$user[$mediaKey]);
        }
    }

    return $user;
}

function getUser($userId) {
    global $pdo;
    $cols   = tableColumns('users');
    $select = ['id', 'first_name', 'last_name', 'email', 'avatar'];
    foreach ([
        'role','is_admin','is_verified','cover_image','organization',
        'specialty','education','country','city','bio',
        'interest1','interest2','interest3','is_deleted','notify_inbox_email',
    ] as $col) {
        if (in_array($col, $cols)) $select[] = $col;
    }
    $stmt = $pdo->prepare("SELECT " . implode(', ', $select) . " FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) return $user;
    if (isUserDeleted($user)) return null;
    $user['is_admin'] = !empty($user['is_admin']);
    if (isset($user['is_verified'])) $user['is_verified'] = (bool)$user['is_verified'];
    if (isset($user['notify_inbox_email'])) {
        $user['notify_inbox_email'] = (int)$user['notify_inbox_email'] !== 0;
    }
    return enrichUserProfile($user);
}

function canModeratePosts($userId) {
    if (!$userId) return false;
    global $pdo;
    $cols   = tableColumns('users');
    $checks = [];
    if (in_array('is_admin', $cols)) $checks[] = 'is_admin = 1';
    if (in_array('role', $cols))     $checks[] = "role IN ('admin', 'backoffice', 'moderator')";
    if (!$checks) return false;
    $stmt = $pdo->prepare(
        "SELECT 1 FROM users WHERE id = ? AND (" . implode(' OR ', $checks) . ") LIMIT 1"
    );
    $stmt->execute([$userId]);
    return (bool)$stmt->fetchColumn();
}

/** Доступ к списку жалоб (модераторы + is_platform_admin). */
function canPlatformAdmin($userId): bool
{
    if (!$userId) return false;
    if (canModeratePosts($userId)) return true;
    global $pdo;
    $cols = tableColumns('users');
    if (!in_array('is_platform_admin', $cols, true)) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT is_platform_admin FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    return (bool)$stmt->fetchColumn();
}

function timeAgo($datetime) {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->y > 0) return $diff->y . ' ' . plural($diff->y, 'год','года','лет')         . ' назад';
    if ($diff->m > 0) return $diff->m . ' ' . plural($diff->m, 'месяц','месяца','месяцев') . ' назад';
    if ($diff->d > 0) return $diff->d . ' ' . plural($diff->d, 'день','дня','дней')         . ' назад';
    if ($diff->h > 0) return $diff->h . ' ' . plural($diff->h, 'час','часа','часов')         . ' назад';
    if ($diff->i > 0) return $diff->i . ' ' . plural($diff->i, 'минуту','минуты','минут')   . ' назад';
    return 'только что';
}
function ensureNotificationsSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            from_user_id INT DEFAULT NULL,
            type VARCHAR(50) NOT NULL,
            post_id INT DEFAULT NULL,
            post_preview VARCHAR(255) DEFAULT NULL,
            post_type VARCHAR(32) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_notif_user (user_id),
            KEY idx_notif_type (type),
            KEY idx_notif_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }
}

function memberDisplayName(array $m): string
{
    return trim((string)($m['first_name'] ?? '') . ' ' . (string)($m['last_name'] ?? ''));
}

function fetchProjectMembersForMentions(PDO $pdo, int $projectId): array
{
    $stmt = $pdo->prepare("
        SELECT u.id AS user_id, u.first_name, u.last_name
        FROM project_members pm
        JOIN users u ON u.id = pm.user_id
        WHERE pm.project_id = ?
    ");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function parseAtMentions(PDO $pdo, int $projectId, string $content): array
{
    $members = fetchProjectMembersForMentions($pdo, $projectId);
    if ($content === '' || !$members) {
        return [];
    }
    $labels = [];
    foreach ($members as $m) {
        $uid = (int)($m['user_id'] ?? 0);
        $name = memberDisplayName($m);
        if ($uid <= 0 || $name === '') {
            continue;
        }
        $labels[] = ['user_id' => $uid, 'label' => '@' . $name, 'len' => mb_strlen($name) + 1];
    }
    usort($labels, fn($a, $b) => $b['len'] <=> $a['len']);
    $found = [];
    foreach ($labels as $item) {
        if (mb_strpos($content, $item['label']) !== false) {
            $found[$item['user_id']] = true;
        }
    }
    return array_map('intval', array_keys($found));
}

function appNotifSentToday(PDO $pdo, int $userId, string $type, int $refId): bool
{
    ensureNotificationsSchema($pdo);
    $stmt = $pdo->prepare("
        SELECT 1 FROM notifications
        WHERE user_id = ? AND type = ? AND post_id = ? AND DATE(created_at) = CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$userId, $type, $refId]);
    return (bool)$stmt->fetchColumn();
}

function insertAppNotification(
    PDO $pdo,
    int $userId,
    ?int $fromUserId,
    string $type,
    int $refId,
    string $preview,
    string $postType = '',
    bool $oncePerDay = false
): void {
    if ($userId <= 0) {
        return;
    }
    if ($fromUserId !== null && $fromUserId > 0 && $fromUserId === $userId) {
        return;
    }
    try {
        ensureNotificationsSchema($pdo);
        if ($oncePerDay && appNotifSentToday($pdo, $userId, $type, $refId)) {
            return;
        }
        $preview = mb_substr(trim($preview), 0, 120);
        $pdo->prepare("
            INSERT INTO notifications (user_id, from_user_id, type, post_id, post_preview, post_type, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ")->execute([
            $userId,
            ($fromUserId !== null && $fromUserId > 0) ? $fromUserId : null,
            $type,
            $refId,
            $preview,
            $postType !== '' ? $postType : null,
        ]);
        if (function_exists('notifyInboxByEmail')) {
            notifyInboxByEmail($pdo, $userId, $type, $fromUserId, $preview, $refId);
        }
    } catch (Throwable $e) {
    }
}

function ensureTaskTimeLogsTable(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS task_time_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            minutes INT UNSIGNED NOT NULL DEFAULT 0,
            note VARCHAR(500) DEFAULT NULL,
            logged_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ttl_task (task_id),
            INDEX idx_ttl_project (project_id),
            INDEX idx_ttl_user (user_id),
            INDEX idx_ttl_logged (logged_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }
}

function ensureProjectActivitySchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS project_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            type VARCHAR(64) NOT NULL DEFAULT 'post',
            meta VARCHAR(1000) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_pactivity_project (project_id),
            KEY idx_pactivity_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $col = $pdo->query("SHOW COLUMNS FROM project_activity LIKE 'type'")->fetch(PDO::FETCH_ASSOC);
        if ($col && stripos((string)($col['Type'] ?? ''), 'enum') !== false) {
            $pdo->exec("ALTER TABLE project_activity MODIFY type VARCHAR(64) NOT NULL DEFAULT 'post'");
        }
        $metaCol = $pdo->query("SHOW COLUMNS FROM project_activity LIKE 'meta'")->fetch(PDO::FETCH_ASSOC);
        if ($metaCol && stripos((string)($metaCol['Type'] ?? ''), 'varchar(500)') !== false) {
            $pdo->exec("ALTER TABLE project_activity MODIFY meta VARCHAR(1000) DEFAULT NULL");
        }
    } catch (Throwable $e) {
    }
}

function logProjectActivity(
    PDO $pdo,
    int $projectId,
    ?int $actorId,
    string $actionType,
    string $entityType = '',
    ?int $entityId = null,
    array $meta = []
): void {
    try {
        ensureProjectActivitySchema($pdo);
        if ($entityType !== '') {
            $meta['entity_type'] = $entityType;
        }
        if ($entityId !== null && $entityId > 0) {
            $meta['entity_id'] = $entityId;
        }
        $metaJson = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
        if ($metaJson !== null && mb_strlen($metaJson) > 990) {
            $metaJson = mb_substr($metaJson, 0, 990);
        }
        $pdo->prepare("INSERT INTO project_activity (project_id, user_id, type, meta) VALUES (?,?,?,?)")
            ->execute([$projectId, ($actorId !== null && $actorId > 0) ? $actorId : null, $actionType, $metaJson]);
    } catch (Throwable $e) {
    }
}

function defaultKanbanColumnDefs(): array
{
    return [
        ['key' => 'backlog', 'label' => 'Backlog', 'color' => '#9ca3af', 'position' => 0, 'is_builtin' => 1],
        ['key' => 'todo', 'label' => 'To Do', 'color' => '#3b82f6', 'position' => 1, 'is_builtin' => 1],
        ['key' => 'in_progress', 'label' => 'In Progress', 'color' => '#f59e0b', 'position' => 2, 'is_builtin' => 1],
        ['key' => 'review', 'label' => 'Review', 'color' => '#8b5cf6', 'position' => 3, 'is_builtin' => 1],
        ['key' => 'done', 'label' => 'Done', 'color' => '#10b981', 'position' => 4, 'is_builtin' => 1],
    ];
}

function ensureProjectKanbanColumnsSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS project_kanban_columns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            col_key VARCHAR(64) NOT NULL,
            label VARCHAR(120) NOT NULL,
            color VARCHAR(32) NOT NULL DEFAULT '#7c3aed',
            position INT NOT NULL DEFAULT 0,
            is_builtin TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_pkbc_proj_col (project_id, col_key),
            KEY idx_pkbc_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $statusCol = $pdo->query("SHOW COLUMNS FROM project_tasks LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
        if ($statusCol && stripos((string)($statusCol['Type'] ?? ''), 'enum') !== false) {
            $pdo->exec("ALTER TABLE project_tasks MODIFY status VARCHAR(64) NOT NULL DEFAULT 'backlog'");
        }
    } catch (Throwable $e) {
    }
}

function ensureProjectKanbanColumns(PDO $pdo, int $projectId): void
{
    ensureProjectKanbanColumnsSchema($pdo);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM project_kanban_columns WHERE project_id = ?');
    $stmt->execute([$projectId]);
    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }
    $ins = $pdo->prepare('INSERT INTO project_kanban_columns (project_id, col_key, label, color, position, is_builtin) VALUES (?,?,?,?,?,?)');
    foreach (defaultKanbanColumnDefs() as $col) {
        $ins->execute([$projectId, $col['key'], $col['label'], $col['color'], $col['position'], $col['is_builtin']]);
    }
}

function fetchProjectKanbanColumns(PDO $pdo, int $projectId): array
{
    ensureProjectKanbanColumns($pdo, $projectId);
    $stmt = $pdo->prepare('SELECT col_key AS `key`, label, color, position, is_builtin FROM project_kanban_columns WHERE project_id = ? ORDER BY position ASC, id ASC');
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['is_builtin'] = (int)($row['is_builtin'] ?? 0);
        $row['position'] = (int)($row['position'] ?? 0);
    }
    unset($row);
    return $rows;
}

function projectKanbanColumnKeys(PDO $pdo, int $projectId): array
{
    return array_column(fetchProjectKanbanColumns($pdo, $projectId), 'key');
}

function validateProjectTaskStatus(PDO $pdo, int $projectId, string $status): string
{
    $status = trim($status);
    if ($status === '') {
        $status = 'backlog';
    }
    $keys = projectKanbanColumnKeys($pdo, $projectId);
    if (!$keys) {
        return $status;
    }
    if (!in_array($status, $keys, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Неизвестный статус колонки']);
        exit;
    }
    return $status;
}

function buildProjectTaskColumns(PDO $pdo, int $projectId, array $tasks): array
{
    $columns = [];
    foreach (fetchProjectKanbanColumns($pdo, $projectId) as $col) {
        $columns[$col['key']] = [];
    }
    $fallback = array_key_first($columns) ?: 'backlog';
    foreach ($tasks as $t) {
        $status = (string)($t['status'] ?? $fallback);
        if (!array_key_exists($status, $columns)) {
            $status = $fallback;
        }
        $columns[$status][] = $t;
    }
    return $columns;
}

function createProjectKanbanColumn(PDO $pdo, int $projectId, string $label, ?string $color = null): array
{
    ensureProjectKanbanColumns($pdo, $projectId);
    $label = trim($label);
    if ($label === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Название столбца обязательно']);
        exit;
    }
    if (mb_strlen($label) > 120) {
        $label = mb_substr($label, 0, 120);
    }
    $palette = ['#6366f1', '#0ea5e9', '#14b8a6', '#f97316', '#ec4899', '#84cc16', '#64748b'];
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM project_kanban_columns WHERE project_id = ?');
    $stmt->execute([$projectId]);
    $position = (int)$stmt->fetchColumn();
    $colKey = 'col_' . substr(bin2hex(random_bytes(4)), 0, 8);
    $colColor = $color && preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : $palette[$position % count($palette)];
    $pdo->prepare('INSERT INTO project_kanban_columns (project_id, col_key, label, color, position, is_builtin) VALUES (?,?,?,?,?,0)')
        ->execute([$projectId, $colKey, $label, $colColor, $position]);
    return [
        'key' => $colKey,
        'label' => $label,
        'color' => $colColor,
        'position' => $position,
        'is_builtin' => 0,
    ];
}

function deleteProjectKanbanColumn(PDO $pdo, int $projectId, string $colKey): void
{
    ensureProjectKanbanColumns($pdo, $projectId);
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM project_kanban_columns WHERE project_id = ?');
    $countStmt->execute([$projectId]);
    if ((int)$countStmt->fetchColumn() <= 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Нельзя удалить последний столбец']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT col_key FROM project_kanban_columns WHERE project_id = ? AND col_key = ?');
    $stmt->execute([$projectId, $colKey]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Столбец не найден']);
        exit;
    }
    $cols = fetchProjectKanbanColumns($pdo, $projectId);
    $fallback = null;
    foreach ($cols as $c) {
        if ($c['key'] !== $colKey) {
            $fallback = $c['key'];
            break;
        }
    }
    if (!$fallback) {
        $fallback = 'backlog';
    }
    $pdo->prepare('UPDATE project_tasks SET status = ? WHERE project_id = ? AND status = ?')
        ->execute([$fallback, $projectId, $colKey]);
    $pdo->prepare('DELETE FROM project_kanban_columns WHERE project_id = ? AND col_key = ?')
        ->execute([$projectId, $colKey]);
}

function reorderProjectKanbanColumns(PDO $pdo, int $projectId, array $orderedKeys): array
{
    ensureProjectKanbanColumns($pdo, $projectId);
    $existing = fetchProjectKanbanColumns($pdo, $projectId);
    $existingKeys = array_column($existing, 'key');
    $orderedKeys = array_values(array_filter(array_map('strval', $orderedKeys)));
    if (count($orderedKeys) !== count($existingKeys)
        || count(array_diff($orderedKeys, $existingKeys)) > 0
        || count(array_diff($existingKeys, $orderedKeys)) > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный порядок столбцов']);
        exit;
    }
    $upd = $pdo->prepare('UPDATE project_kanban_columns SET position = ? WHERE project_id = ? AND col_key = ?');
    foreach ($orderedKeys as $pos => $key) {
        $upd->execute([$pos, $projectId, $key]);
    }
    return fetchProjectKanbanColumns($pdo, $projectId);
}

require_once __DIR__ . '/notify_email.php';

function plural($n, $one, $two, $five) {
    $n = abs($n) % 100;
    if ($n >= 11 && $n <= 19) return $five;
    $n = $n % 10;
    if ($n === 1) return $one;
    if ($n >= 2 && $n <= 4) return $two;
    return $five;
}
