<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

function ensureNotificationReadsSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS notification_reads (
        user_id    INT NOT NULL,
        notif_key  VARCHAR(64) NOT NULL,
        read_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, notif_key),
        KEY idx_nr_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function buildNotifKey(array $n): string
{
    return hash('sha256', implode('|', [
        (string)($n['type'] ?? ''),
        (string)($n['ref_id'] ?? ''),
        (string)($n['from_user_id'] ?? ''),
        (string)($n['created_at'] ?? ''),
    ]));
}

function fetchReadNotifKeys(PDO $pdo, int $userId): array
{
    ensureNotificationReadsSchema($pdo);
    $stmt = $pdo->prepare('SELECT notif_key FROM notification_reads WHERE user_id = ?');
    $stmt->execute([$userId]);
    $keys = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $k) {
        $keys[(string)$k] = true;
    }
    return $keys;
}

function markNotifKeysRead(PDO $pdo, int $userId, array $keys): void
{
    ensureNotificationReadsSchema($pdo);
    $ins = $pdo->prepare('INSERT IGNORE INTO notification_reads (user_id, notif_key) VALUES (?, ?)');
    foreach ($keys as $key) {
        $key = trim((string)$key);
        if ($key !== '' && strlen($key) <= 64) {
            $ins->execute([$userId, $key]);
        }
    }
}

try {
    $userId = verifyToken();
    ensureNotificationsSchema($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $since = trim($_GET['since'] ?? '');

        // Реальные имена колонок
        $postCols = $pdo->query('SHOW COLUMNS FROM posts')->fetchAll(PDO::FETCH_COLUMN);
        $postTextCol = in_array('text', $postCols, true) ? 'text' : 'content';

        $commentCols = $pdo->query('SHOW COLUMNS FROM comments')->fetchAll(PDO::FETCH_COLUMN);
        $commentTextCol = in_array('text', $commentCols, true) ? 'text' : 'content';

        $notifications = [];

        // ── Лайки на посты пользователя ──────────────────────────
        try {
            $likesSql = "
                SELECT 'like' AS type, l.created_at, l.post_id AS ref_id,
                       u.id AS from_user_id,
                       u.first_name, u.last_name, u.avatar, u.is_verified,
                       LEFT(p.{$postTextCol}, 120) AS post_preview
                FROM likes l
                JOIN users u ON l.user_id = u.id
                JOIN posts p ON l.post_id = p.id
                WHERE p.user_id = ? AND l.user_id != ?
            ";
            $params = [$userId, $userId];
            if ($since !== '') {
                $likesSql .= ' AND l.created_at > ?';
                $params[] = $since;
            }
            $likesSql .= ' ORDER BY l.created_at DESC LIMIT 30';
            $stmt = $pdo->prepare($likesSql);
            $stmt->execute($params);
            $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {}

        // ── Комментарии на посты пользователя ────────────────────
        try {
            $commentsSql = "
                SELECT 'comment' AS type, c.created_at, c.post_id AS ref_id,
                       u.id AS from_user_id,
                       u.first_name, u.last_name, u.avatar, u.is_verified,
                       LEFT(c.{$commentTextCol}, 120) AS post_preview
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN posts p ON c.post_id = p.id
                WHERE p.user_id = ? AND c.user_id != ?
            ";
            $params = [$userId, $userId];
            if ($since !== '') {
                $commentsSql .= ' AND c.created_at > ?';
                $params[] = $since;
            }
            $commentsSql .= ' ORDER BY c.created_at DESC LIMIT 30';
            $stmt = $pdo->prepare($commentsSql);
            $stmt->execute($params);
            $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {}

        // ── Репосты постов пользователя ──────────────────────────
        try {
            $hasReposts = (bool)$pdo->query("
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reposts' LIMIT 1
            ")->fetchColumn();

            if ($hasReposts) {
                $repostsSql = "
                    SELECT 'repost' AS type, r.created_at, r.post_id AS ref_id,
                           u.id AS from_user_id,
                           u.first_name, u.last_name, u.avatar, u.is_verified,
                           LEFT(p.{$postTextCol}, 120) AS post_preview
                    FROM reposts r
                    JOIN users u ON r.user_id = u.id
                    JOIN posts p ON p.id = r.post_id
                    WHERE p.user_id = ? AND r.user_id != ?
                ";
                $params = [$userId, $userId];
                if ($since !== '') {
                    $repostsSql .= ' AND r.created_at > ?';
                    $params[] = $since;
                }
                $repostsSql .= ' ORDER BY r.created_at DESC LIMIT 30';
                $stmt = $pdo->prepare($repostsSql);
                $stmt->execute($params);
                $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Throwable $e) {}

        // ── Входящие заявки в друзья ─────────────────────────────
        try {
            $friendsSql = "
                SELECT 'friend_request' AS type, fr.created_at, fr.id AS ref_id,
                       u.id AS from_user_id,
                       u.first_name, u.last_name, u.avatar, u.is_verified,
                       '' AS post_preview
                FROM friend_requests fr
                JOIN users u ON fr.from_id = u.id
                WHERE fr.to_id = ? AND fr.status = 'pending'
            ";
            $params = [$userId];
            if ($since !== '') {
                $friendsSql .= ' AND fr.created_at > ?';
                $params[] = $since;
            }
            $friendsSql .= ' ORDER BY fr.created_at DESC LIMIT 20';
            $stmt = $pdo->prepare($friendsSql);
            $stmt->execute($params);
            $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {}

        // ── Лайки на посты компании ──────────────────────────────
        try {
            $hasCoLikes = (bool)$pdo->query("
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_likes' LIMIT 1
            ")->fetchColumn();
            if ($hasCoLikes) {
                $coLikesSql = "
                    SELECT 'like' AS type, cl.created_at, cl.post_id AS ref_id,
                           u.id AS from_user_id,
                           u.first_name, u.last_name, u.avatar, u.is_verified,
                           LEFT(p.text, 120) AS post_preview,
                           'company' AS post_type,
                           p.company_id AS company_id
                    FROM company_likes cl
                    JOIN users u ON cl.user_id = u.id
                    JOIN company_posts p ON cl.post_id = p.id
                    JOIN company_profiles c ON c.id = p.company_id
                    WHERE c.user_id = ? AND cl.user_id != ?
                ";
                $params = [$userId, $userId];
                if ($since !== '') {
                    $coLikesSql .= ' AND cl.created_at > ?';
                    $params[] = $since;
                }
                $coLikesSql .= ' ORDER BY cl.created_at DESC LIMIT 30';
                $stmt = $pdo->prepare($coLikesSql);
                $stmt->execute($params);
                $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Throwable $e) {}

        // ── Комментарии на посты компании ────────────────────────
        try {
            $hasCoComments = (bool)$pdo->query("
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_comments' LIMIT 1
            ")->fetchColumn();
            if ($hasCoComments) {
                $coCommentsSql = "
                    SELECT 'comment' AS type, c.created_at, c.post_id AS ref_id,
                           u.id AS from_user_id,
                           u.first_name, u.last_name, u.avatar, u.is_verified,
                           LEFT(c.text, 120) AS post_preview,
                           'company' AS post_type,
                           p.company_id AS company_id
                    FROM company_comments c
                    JOIN users u ON c.user_id = u.id
                    JOIN company_posts p ON c.post_id = p.id
                    JOIN company_profiles cp ON cp.id = p.company_id
                    WHERE cp.user_id = ? AND c.user_id != ?
                ";
                $params = [$userId, $userId];
                if ($since !== '') {
                    $coCommentsSql .= ' AND c.created_at > ?';
                    $params[] = $since;
                }
                $coCommentsSql .= ' ORDER BY c.created_at DESC LIMIT 30';
                $stmt = $pdo->prepare($coCommentsSql);
                $stmt->execute($params);
                $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Throwable $e) {}

        // ── Репосты постов компании ──────────────────────────────
        try {
            $hasCoReposts = (bool)$pdo->query("
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_reposts' LIMIT 1
            ")->fetchColumn();
            if ($hasCoReposts) {
                $coRepostsSql = "
                    SELECT 'repost' AS type, r.created_at, r.post_id AS ref_id,
                           u.id AS from_user_id,
                           u.first_name, u.last_name, u.avatar, u.is_verified,
                           LEFT(p.text, 120) AS post_preview,
                           'company' AS post_type,
                           p.company_id AS company_id
                    FROM company_reposts r
                    JOIN users u ON r.user_id = u.id
                    JOIN company_posts p ON r.post_id = p.id
                    JOIN company_profiles c ON c.id = p.company_id
                    WHERE c.user_id = ? AND r.user_id != ?
                ";
                $params = [$userId, $userId];
                if ($since !== '') {
                    $coRepostsSql .= ' AND r.created_at > ?';
                    $params[] = $since;
                }
                $coRepostsSql .= ' ORDER BY r.created_at DESC LIMIT 30';
                $stmt = $pdo->prepare($coRepostsSql);
                $stmt->execute($params);
                $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Throwable $e) {}

        // ── Идеи, отклики на вакансии, статусы откликов ─────────
        try {
            $frCol = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'feature_request_id'")->fetch();
            $refExpr = $frCol ? 'COALESCE(n.feature_request_id, n.post_id)' : 'n.post_id';
            $storedSql = "
                SELECT n.type, n.created_at, {$refExpr} AS ref_id,
                       n.from_user_id,
                       u.first_name, u.last_name, u.avatar, u.is_verified,
                       n.post_preview, n.post_type
                FROM notifications n
                LEFT JOIN users u ON u.id = n.from_user_id
                WHERE n.user_id = ? AND n.type IN ('feature_status', 'job_application', 'job_status')
            ";
            $params = [$userId];
            if ($since !== '') {
                $storedSql .= ' AND n.created_at > ?';
                $params[] = $since;
            }
            $storedSql .= ' ORDER BY n.created_at DESC LIMIT 30';
            $stmt = $pdo->prepare($storedSql);
            $stmt->execute($params);
            $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {}

        foreach ($notifications as &$n) {
            $n['from_user_id'] = (int)($n['from_user_id'] ?? 0);
            $n['ref_id'] = isset($n['ref_id']) ? (int)$n['ref_id'] : null;
            if (isset($n['company_id'])) {
                $n['company_id'] = (int)$n['company_id'];
            }
            $n['key'] = buildNotifKey($n);
            $n['created_at'] = utcDate($n['created_at']);
            $n['time_ago']   = timeAgo($n['created_at']);
        }
        unset($n);

        usort($notifications, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        $notifications = array_slice($notifications, 0, 40);

        $readKeys = fetchReadNotifKeys($pdo, (int)$userId);
        $unreadCount = 0;
        foreach ($notifications as &$n) {
            $n['is_read'] = isset($readKeys[$n['key']]);
            if (!$n['is_read']) $unreadCount++;
        }
        unset($n);

        echo json_encode([
            'notifications' => $notifications,
            'count'         => count($notifications),
            'unread_count'  => $unreadCount,
            'server_time'   => utcDate(date('Y-m-d H:i:s')),
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $keys = [];
        if (!empty($data['read_keys']) && is_array($data['read_keys'])) {
            $keys = $data['read_keys'];
        } elseif (!empty($data['read_key'])) {
            $keys = [(string)$data['read_key']];
        }
        if (!$keys) {
            http_response_code(400);
            echo json_encode(['error' => 'read_key or read_keys required']);
            exit;
        }
        markNotifKeysRead($pdo, (int)$userId, $keys);

        // Пересчитываем unread для ответа (упрощённо — без полного rebuild)
        $readKeys = fetchReadNotifKeys($pdo, (int)$userId);
        echo json_encode([
            'ok'           => true,
            'marked'       => count($keys),
            'read_keys'    => array_values(array_map('strval', $keys)),
            'unread_count' => null,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
