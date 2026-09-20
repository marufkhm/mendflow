<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

require_once 'db.php';

function repostJson($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ensureRepostsTable(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reposts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                post_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_post (user_id, post_id),
                INDEX idx_reposts_user (user_id),
                INDEX idx_reposts_post (post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {}
}

function ensureCompanyRepostsTable(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS company_reposts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_co_repost (post_id, user_id),
                KEY idx_co_reposts_post (post_id),
                KEY idx_co_reposts_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {}
}

function repostPostType(array $data = []): string {
    $type = $data['post_type'] ?? $_GET['post_type'] ?? '';
    return $type === 'company' ? 'company' : 'user';
}

function postTextImageCols(PDO $pdo): array {
    $cols = $pdo->query('SHOW COLUMNS FROM posts')->fetchAll(PDO::FETCH_COLUMN);
    return [
        'text'  => in_array('text', $cols, true) ? 'text' : 'content',
        'image' => in_array('image_url', $cols, true) ? 'image_url' : 'image',
    ];
}

function hydrateRepostRow(array $row, ?int $viewerId): array {
    $row['is_repost']       = true;
    $row['repost_id']       = (int)($row['repost_id'] ?? 0);
    $row['reposted_at']     = utcDate($row['reposted_at'] ?? $row['created_at'] ?? '');
    $row['time_ago']        = timeAgo($row['reposted_at']);
    $row['created_at']      = utcDate($row['created_at'] ?? '');
    $row['user_liked']      = !empty($row['user_liked']);
    $row['user_reposted']   = !empty($row['user_reposted']);
    $row['likes_count']     = (int)($row['likes_count'] ?? 0);
    $row['comments_count']  = (int)($row['comments_count'] ?? 0);
    $row['reposts_count']   = (int)($row['reposts_count'] ?? 0);
    $row['author_type']     = 'user';
    $row['author_name']     = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $row['repost_author_name'] = trim(($row['repost_first_name'] ?? '') . ' ' . ($row['repost_last_name'] ?? ''));
    if (!isset($row['content'])) $row['content'] = $row['text'] ?? '';
    $row['can_delete'] = $viewerId && (int)($row['repost_user_id'] ?? 0) === (int)$viewerId;
    return $row;
}

try {
    global $pdo;
    ensureRepostsTable($pdo);
    $viewerId = verifyTokenSoft();
    $method   = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $userId = (int)($_GET['user_id'] ?? 0);
        if (!$userId) {
            repostJson(['error' => 'user_id required'], 400);
        }

        $limit  = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $cols   = postTextImageCols($pdo);
        $textCol  = $cols['text'];
        $imageCol = $cols['image'];
        $viewerSql = $viewerId ? (int)$viewerId : 0;

        $stmt = $pdo->prepare("
            SELECT
                r.id AS repost_id,
                r.created_at AS reposted_at,
                r.user_id AS repost_user_id,
                ru.first_name AS repost_first_name,
                ru.last_name AS repost_last_name,
                ru.avatar AS repost_avatar,
                p.id,
                p.{$textCol} AS text,
                p.{$imageCol} AS image_url,
                p.created_at,
                u.id AS user_id,
                u.first_name,
                u.last_name,
                u.avatar,
                u.is_verified,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
                (SELECT COUNT(*) FROM reposts WHERE post_id = p.id) AS reposts_count,
                " . ($viewerSql ? "(SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = {$viewerSql})" : "0") . " AS user_liked,
                " . ($viewerSql ? "(SELECT COUNT(*) FROM reposts WHERE post_id = p.id AND user_id = {$viewerSql})" : "0") . " AS user_reposted
            FROM reposts r
            JOIN posts p ON p.id = r.post_id
            JOIN users u ON u.id = p.user_id
            JOIN users ru ON ru.id = r.user_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        $reposts = array_map(fn($row) => hydrateRepostRow($row, $viewerId), $stmt->fetchAll(PDO::FETCH_ASSOC));

        repostJson(['reposts' => $reposts, 'count' => count($reposts)]);
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $data['action'] ?? 'toggle';
        $postId = (int)($data['post_id'] ?? 0);
        $postType = repostPostType($data);

        if (!$postId) {
            repostJson(['error' => 'post_id required'], 400);
        }

        if ($postType === 'company') {
            ensureCompanyRepostsTable($pdo);
            $st = $pdo->prepare(
                'SELECT p.id, cp.user_id FROM company_posts p
                 JOIN company_profiles cp ON cp.id = p.company_id
                 WHERE p.id = ? LIMIT 1'
            );
            $st->execute([$postId]);
            $post = $st->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                repostJson(['error' => 'Post not found'], 404);
            }
            if ((int)$post['user_id'] === (int)$userId) {
                repostJson(['error' => 'Нельзя репостить свой пост'], 400);
            }
            $repostsTable = 'company_reposts';
        } else {
            $st = $pdo->prepare('SELECT id, user_id FROM posts WHERE id = ? LIMIT 1');
            $st->execute([$postId]);
            $post = $st->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                repostJson(['error' => 'Post not found'], 404);
            }
            if ((int)$post['user_id'] === (int)$userId) {
                repostJson(['error' => 'Нельзя репостить свой пост'], 400);
            }
            $repostsTable = 'reposts';
        }

        $st = $pdo->prepare("SELECT id FROM {$repostsTable} WHERE user_id = ? AND post_id = ? LIMIT 1");
        $st->execute([$userId, $postId]);
        $existing = $st->fetchColumn();

        if ($action === 'toggle') {
            if ($existing) {
                $pdo->prepare("DELETE FROM {$repostsTable} WHERE user_id = ? AND post_id = ?")->execute([$userId, $postId]);
                $reposted = false;
            } else {
                $pdo->prepare("INSERT INTO {$repostsTable} (user_id, post_id, created_at) VALUES (?, ?, NOW())")->execute([$userId, $postId]);
                $reposted = true;
            }
        } elseif ($action === 'add') {
            if (!$existing) {
                $pdo->prepare("INSERT INTO {$repostsTable} (user_id, post_id, created_at) VALUES (?, ?, NOW())")->execute([$userId, $postId]);
            }
            $reposted = true;
        } elseif ($action === 'remove') {
            $pdo->prepare("DELETE FROM {$repostsTable} WHERE user_id = ? AND post_id = ?")->execute([$userId, $postId]);
            $reposted = false;
        } else {
            repostJson(['error' => 'Invalid action'], 400);
        }

        $st = $pdo->prepare("SELECT COUNT(*) FROM {$repostsTable} WHERE post_id = ?");
        $st->execute([$postId]);
        $count = (int)$st->fetchColumn();

        repostJson([
            'success'       => true,
            'reposted'      => $reposted,
            'reposts_count' => $count,
        ]);
    }

    repostJson(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    repostJson(['error' => 'Server error: ' . $e->getMessage()], 500);
}
