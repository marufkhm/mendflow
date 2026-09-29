<?php
/**
 * Global search — people, projects, posts, universities
 * GET /search.php?q=&limit=8&type=all|people|projects|posts|universities
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';

function searchJson($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function searchPostsTextCol(PDO $pdo): string
{
    static $col = null;
    if ($col !== null) {
        return $col;
    }
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM posts')->fetchAll(PDO::FETCH_COLUMN);
        $col = in_array('text', $cols, true) ? 'text' : 'content';
    } catch (Throwable $e) {
        $col = 'text';
    }
    return $col;
}

function searchEnsurePostsFulltext(PDO $pdo, string $textCol): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        $st = $pdo->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'ft_posts_text' LIMIT 1"
        );
        $st->execute();
        if (!$st->fetchColumn()) {
            $pdo->exec("ALTER TABLE posts ADD FULLTEXT INDEX ft_posts_text (`{$textCol}`)");
        }
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }
    return $ok;
}

function searchFulltextQuery(string $q): string
{
    $parts = preg_split('/\s+/u', trim($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (!$parts) {
        return '';
    }
    return implode(' ', array_map(static function ($w) {
        $w = preg_replace('/[^\p{L}\p{N}]+/u', '', $w);
        return $w !== '' ? '+' . $w . '*' : '';
    }, $parts));
}

function searchUserCols(PDO $pdo): array
{
    try {
        return $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    searchJson(['error' => 'Method not allowed'], 405);
}

$q = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? 'all');
$limit = min(12, max(1, (int)($_GET['limit'] ?? 8)));
$userId = verifyTokenSoft();

if (mb_strlen($q) < 2) {
    searchJson(['q' => $q, 'people' => [], 'projects' => [], 'posts' => [], 'universities' => []]);
}

$like = '%' . $q . '%';
$ftQ = searchFulltextQuery($q);
$out = ['q' => $q, 'people' => [], 'projects' => [], 'posts' => [], 'universities' => []];

try {
    if ($type === 'all' || $type === 'people') {
        $cols = searchUserCols($pdo);
        $roleFilter = in_array('role', $cols, true)
            ? "AND (u.role IS NULL OR u.role NOT IN ('university', 'company'))"
            : '';
        $extra = '';
        foreach (['organization', 'specialty', 'city'] as $c) {
            if (in_array($c, $cols, true)) {
                $extra .= ", u.{$c}";
            }
        }
        $blocked = ($userId && function_exists('mfBlockedFilterSql'))
            ? mfBlockedFilterSql($pdo, (int)$userId, 'u.id')
            : '';
        $blockedSql = $blocked ? "AND {$blocked}" : '';
        $selfSql = $userId ? 'AND u.id != ' . (int)$userId : '';

        $st = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.avatar{$extra}
            FROM users u
            WHERE (
                CONCAT(u.first_name, ' ', u.last_name) LIKE ?
                OR u.first_name LIKE ? OR u.last_name LIKE ?
                OR u.email LIKE ?
                " . (in_array('organization', $cols, true) ? 'OR u.organization LIKE ?' : '') . "
                " . (in_array('specialty', $cols, true) ? 'OR u.specialty LIKE ?' : '') . "
            )
            {$roleFilter} {$selfSql} {$blockedSql}
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $params = [$like, $like, $like, $like];
        if (in_array('organization', $cols, true)) {
            $params[] = $like;
        }
        if (in_array('specialty', $cols, true)) {
            $params[] = $like;
        }
        $params[] = $limit;
        $st->execute($params);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $out['people'][] = [
                'id' => (int)$row['id'],
                'name' => $name ?: 'Участник',
                'avatar' => $row['avatar'] ?? null,
                'subtitle' => $row['organization'] ?? $row['specialty'] ?? '',
            ];
        }
    }

    if ($type === 'all' || $type === 'projects') {
        require_once __DIR__ . '/project_slug.php';
        ensureProjectSlugColumn($pdo);
        $hasSlug = false;
        try {
            $hasSlug = (bool)$pdo->query("SHOW COLUMNS FROM projects LIKE 'slug'")->fetch();
        } catch (Throwable $e) {}

        $slugCol = $hasSlug ? ', p.slug' : ", NULL AS slug";
        $slugWhere = $hasSlug ? ' OR p.slug LIKE ?' : '';
        $params = [$like, $like, $like];
        if ($hasSlug) {
            $params[] = $like;
        }
        $params[] = $limit;

        $st = $pdo->prepare("
            SELECT p.id, p.title, p.description, p.category, p.stage, p.cover_url{$slugCol},
                   u.first_name, u.last_name
            FROM projects p
            JOIN users u ON p.owner_id = u.id
            WHERE p.is_public = 1
              AND (p.title LIKE ? OR p.description LIKE ? OR p.tags LIKE ?{$slugWhere})
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $st->execute($params);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out['projects'][] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'slug' => $row['slug'] ?? null,
                'category' => $row['category'],
                'cover_url' => $row['cover_url'],
                'owner' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            ];
        }
    }

    if ($type === 'all' || $type === 'posts') {
        $textCol = searchPostsTextCol($pdo);
        $useFt = $ftQ !== '' && searchEnsurePostsFulltext($pdo, $textCol);
        $blocked = ($userId && function_exists('mfBlockedFilterSql'))
            ? mfBlockedFilterSql($pdo, (int)$userId, 'p.user_id')
            : '';
        $blockedSql = $blocked ? "AND {$blocked}" : '';

        if ($useFt) {
            $st = $pdo->prepare("
                SELECT p.id, p.{$textCol} AS body, p.created_at,
                       u.id AS user_id, u.first_name, u.last_name, u.avatar,
                       MATCH(p.{$textCol}) AGAINST(? IN BOOLEAN MODE) AS relevance
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE MATCH(p.{$textCol}) AGAINST(? IN BOOLEAN MODE) {$blockedSql}
                ORDER BY relevance DESC, p.created_at DESC
                LIMIT ?
            ");
            $st->execute([$ftQ, $ftQ, $limit]);
        } else {
            $st = $pdo->prepare("
                SELECT p.id, p.{$textCol} AS body, p.created_at,
                       u.id AS user_id, u.first_name, u.last_name, u.avatar
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.{$textCol} LIKE ? {$blockedSql}
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $st->execute([$like, $limit]);
        }
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $body = trim(strip_tags((string)($row['body'] ?? '')));
            if (mb_strlen($body) > 140) {
                $body = mb_substr($body, 0, 140) . '…';
            }
            $out['posts'][] = [
                'id' => (int)$row['id'],
                'preview' => $body,
                'author' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'author_id' => (int)$row['user_id'],
                'avatar' => $row['avatar'] ?? null,
            ];
        }
    }

    if ($type === 'all' || $type === 'universities') {
        try {
            $tbl = $pdo->query("SHOW TABLES LIKE 'university_profiles'")->fetch();
            if ($tbl) {
                $st = $pdo->prepare("
                    SELECT up.id, up.university_name, up.city, up.logo
                    FROM university_profiles up
                    WHERE up.university_name LIKE ?
                    ORDER BY up.university_name ASC
                    LIMIT ?
                ");
                $st->execute([$like, $limit]);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $out['universities'][] = [
                        'id' => (int)$row['id'],
                        'name' => $row['university_name'],
                        'city' => $row['city'] ?? '',
                        'logo' => $row['logo'] ?? null,
                    ];
                }
            }
        } catch (Throwable $e) {}
    }

    searchJson($out);
} catch (Throwable $e) {
    searchJson(['error' => 'Search failed', 'q' => $q], 500);
}
