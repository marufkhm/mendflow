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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к БД']);
    exit;
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
    $stmt = $pdo->prepare("SELECT user_id FROM sessions WHERE token = ? AND expires_at > NOW()");
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
    $stmt = $pdo->prepare("SELECT user_id FROM sessions WHERE token = ? AND expires_at > NOW()");
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
        'interest1','interest2','interest3',
    ] as $col) {
        if (in_array($col, $cols)) $select[] = $col;
    }
    $stmt = $pdo->prepare("SELECT " . implode(', ', $select) . " FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) return $user;
    $user['is_admin'] = !empty($user['is_admin']);
    if (isset($user['is_verified'])) $user['is_verified'] = (bool)$user['is_verified'];
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

function plural($n, $one, $two, $five) {
    $n = abs($n) % 100;
    if ($n >= 11 && $n <= 19) return $five;
    $n = $n % 10;
    if ($n === 1) return $one;
    if ($n >= 2 && $n <= 4) return $two;
    return $five;
}
