<?php
/**
 * articles.php — long-form articles with bookmarks & useful reactions
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';

function artJson($data, $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function artTableExists(PDO $pdo, string $table): bool
{
    try {
        $st = $pdo->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1"
        );
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function ensureArticlesSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!artTableExists($pdo, 'articles')) {
        try {
            $pdo->exec("CREATE TABLE articles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                author_id INT NOT NULL,
                title VARCHAR(200) NOT NULL DEFAULT '',
                content LONGTEXT,
                cover_image VARCHAR(500),
                tags VARCHAR(300),
                status ENUM('draft','published') NOT NULL DEFAULT 'draft',
                reading_time_min INT NOT NULL DEFAULT 0,
                bookmarks_count INT NOT NULL DEFAULT 0,
                useful_count INT NOT NULL DEFAULT 0,
                views_count INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                published_at TIMESTAMP NULL,
                INDEX idx_art_author (author_id),
                INDEX idx_art_status (status),
                INDEX idx_art_published (published_at),
                INDEX idx_art_useful (useful_count),
                INDEX idx_art_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    } else {
        try {
            $st = $pdo->prepare(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'articles'
                   AND COLUMN_NAME = 'views_count' LIMIT 1"
            );
            $st->execute();
            if (!$st->fetchColumn()) {
                $pdo->exec('ALTER TABLE articles ADD COLUMN views_count INT NOT NULL DEFAULT 0 AFTER useful_count');
            }
        } catch (Throwable $e) {
        }
    }

    if (!artTableExists($pdo, 'article_bookmarks')) {
        try {
            $pdo->exec("CREATE TABLE article_bookmarks (
                user_id INT NOT NULL,
                article_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, article_id),
                INDEX idx_ab_article (article_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!artTableExists($pdo, 'article_reactions')) {
        try {
            $pdo->exec("CREATE TABLE article_reactions (
                user_id INT NOT NULL,
                article_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, article_id),
                INDEX idx_ar_article (article_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }
}

function artParseTags(?string $tags): array
{
    if ($tags === null || trim($tags) === '') {
        return [];
    }
    $parts = preg_split('/[,#]+/u', $tags) ?: [];
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '' && mb_strlen($p) <= 40) {
            $out[] = $p;
        }
    }
    return array_values(array_unique($out));
}

function artTagsString(array $tags): string
{
    return implode(', ', array_slice($tags, 0, 12));
}

function artReadingTimeMin(?string $content): int
{
    $text = trim(strip_tags((string)$content));
    if ($text === '') {
        return 1;
    }
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return max(1, (int)ceil(count($words) / 200));
}

function artPreview(?string $content, int $max = 150): string
{
    $plain = trim(preg_replace('/[#*`>\[\]_\-]+/u', ' ', (string)$content));
    $plain = preg_replace('/\s+/u', ' ', $plain) ?: '';
    if (mb_strlen($plain) <= $max) {
        return $plain;
    }
    return mb_substr($plain, 0, $max) . '…';
}

function artStorageMediaPath(?string $url): ?string
{
    if ($url === null) {
        return null;
    }
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    $filename = mfUploadsFilename($url);
    if ($filename !== null && $filename !== '') {
        return '/uploads/' . $filename;
    }
    if (preg_match('#(/uploads/[^/?#\s]+)#i', $url, $m)) {
        return $m[1];
    }
    if (preg_match('#^uploads/([^/?#\s]+)#i', $url, $m)) {
        return '/uploads/' . $m[1];
    }
    return $url;
}

function artNormalizeMarkdownMedia(string $content): string
{
    if ($content === '') {
        return $content;
    }
    return (string)preg_replace_callback(
        '/!\[([^\]]*)\]\(([^)]+)\)/',
        static function (array $m): string {
            $stored = artStorageMediaPath(trim($m[2]));
            return '![' . $m[1] . '](' . ($stored ?: $m[2]) . ')';
        },
        $content
    );
}

function artResolveMarkdownMediaForDisplay(string $content): string
{
    if ($content === '') {
        return $content;
    }
    return (string)preg_replace_callback(
        '/!\[([^\]]*)\]\(([^)]+)\)/',
        static function (array $m): string {
            $filename = mfUploadsFilename(trim($m[2]));
            if ($filename !== null && $filename !== '') {
                return '![' . $m[1] . '](' . mfUploadPublicUrl($filename) . ')';
            }
            return $m[0];
        },
        $content
    );
}

function artShapeAuthor(array $row): array
{
    return [
        'id'          => (int)($row['author_id'] ?? $row['user_id'] ?? 0),
        'first_name'  => $row['first_name'] ?? '',
        'last_name'   => $row['last_name'] ?? '',
        'avatar'      => $row['avatar'] ?? null,
        'is_verified' => !empty($row['is_verified']),
        'name'        => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
    ];
}

function artShapeArticle(array $row, ?array $extra = null): array
{
    $tags = artParseTags($row['tags'] ?? '');
    $item = [
        'id'               => (int)$row['id'],
        'author_id'        => (int)$row['author_id'],
        'title'            => $row['title'] ?? '',
        'content'          => artResolveMarkdownMediaForDisplay($row['content'] ?? ''),
        'cover_image'      => normalizeMediaUrl(artStorageMediaPath($row['cover_image'] ?? null)),
        'tags'             => $tags,
        'tags_raw'         => $row['tags'] ?? '',
        'status'           => $row['status'] ?? 'draft',
        'reading_time_min' => (int)($row['reading_time_min'] ?? 0),
        'bookmarks_count'  => (int)($row['bookmarks_count'] ?? 0),
        'useful_count'     => (int)($row['useful_count'] ?? 0),
        'views_count'      => (int)($row['views_count'] ?? 0),
        'created_at'       => utcDate($row['created_at'] ?? null),
        'updated_at'       => utcDate($row['updated_at'] ?? null),
        'published_at'     => utcDate($row['published_at'] ?? null),
        'time_ago'         => timeAgo($row['published_at'] ?? $row['created_at'] ?? null),
        'updated_ago'      => timeAgo($row['updated_at'] ?? $row['created_at'] ?? null),
        'preview'          => artPreview($row['content'] ?? ''),
    ];
    if ($extra) {
        $item = array_merge($item, $extra);
    }
    return $item;
}

function artFetchArticle(PDO $pdo, int $id, ?int $viewerId, bool $trackView = false): ?array
{
    $st = $pdo->prepare(
        'SELECT a.*, u.first_name, u.last_name, u.avatar, u.is_verified
         FROM articles a
         JOIN users u ON u.id = a.author_id
         WHERE a.id = ? LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    if ($trackView && ($row['status'] ?? '') === 'published') {
        $pdo->prepare('UPDATE articles SET views_count = views_count + 1 WHERE id = ?')->execute([$id]);
        $row['views_count'] = (int)($row['views_count'] ?? 0) + 1;
    }

    $author = artShapeAuthor($row);
    $extra = [
        'author'      => $author,
        'author_name' => $author['name'],
        'is_author'   => $viewerId && (int)$row['author_id'] === (int)$viewerId,
        'is_bookmarked' => false,
        'user_useful'   => false,
    ];

    if ($viewerId) {
        $bs = $pdo->prepare('SELECT 1 FROM article_bookmarks WHERE user_id = ? AND article_id = ? LIMIT 1');
        $bs->execute([(int)$viewerId, $id]);
        $extra['is_bookmarked'] = (bool)$bs->fetchColumn();

        $rs = $pdo->prepare('SELECT 1 FROM article_reactions WHERE user_id = ? AND article_id = ? LIMIT 1');
        $rs->execute([(int)$viewerId, $id]);
        $extra['user_useful'] = (bool)$rs->fetchColumn();
    }

    return artShapeArticle($row, $extra);
}

try {
    ensureArticlesSchema($pdo);

    $method = $_SERVER['REQUEST_METHOD'];
    $viewerId = verifyTokenSoft();
    $action = trim($_GET['action'] ?? $_POST['action'] ?? '');

    if ($method === 'GET') {
        if ($action === 'list' || $action === '') {
            $tab = trim($_GET['tab'] ?? 'popular');
            if (!in_array($tab, ['popular', 'new', 'my', 'bookmarks', 'drafts'], true)) {
                $tab = 'popular';
            }
            if (in_array($tab, ['my', 'bookmarks', 'drafts'], true) && !$viewerId) {
                artJson(['error' => 'Требуется авторизация'], 401);
            }

            $tag = trim($_GET['tag'] ?? '');
            $where = '1=1';
            $params = [];
            $join = '';
            $order = 'a.published_at DESC';

            if ($tab === 'my') {
                $where .= " AND a.author_id = ? AND a.status = 'published'";
                $params[] = (int)$viewerId;
                $order = 'a.published_at DESC, a.created_at DESC';
            } elseif ($tab === 'drafts') {
                $where .= " AND a.author_id = ? AND a.status = 'draft'";
                $params[] = (int)$viewerId;
                $order = 'a.updated_at DESC, a.created_at DESC';
            } elseif ($tab === 'bookmarks') {
                $join = ' INNER JOIN article_bookmarks ab ON ab.article_id = a.id AND ab.user_id = ?';
                $params[] = (int)$viewerId;
                $where .= " AND a.status = 'published'";
                $order = 'ab.created_at DESC';
            } else {
                $where .= " AND a.status = 'published'";
                if ($tab === 'popular') {
                    $order = 'a.useful_count DESC, a.bookmarks_count DESC, a.published_at DESC';
                } else {
                    $order = 'a.published_at DESC, a.created_at DESC';
                }
            }

            if ($tag !== '') {
                $where .= ' AND (a.tags LIKE ? OR a.tags LIKE ? OR a.tags LIKE ?)';
                $params[] = $tag . ',%';
                $params[] = '%, ' . $tag . ',%';
                $params[] = '%, ' . $tag;
            }

            $sql = "
                SELECT a.*, u.first_name, u.last_name, u.avatar, u.is_verified
                FROM articles a
                JOIN users u ON u.id = a.author_id
                {$join}
                WHERE {$where}
                ORDER BY {$order}
                LIMIT 50
            ";
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $items = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $author = artShapeAuthor($row);
                $extra = [
                    'author'      => $author,
                    'author_name' => $author['name'],
                    'is_author'   => $viewerId && (int)$row['author_id'] === (int)$viewerId,
                ];
                if ($viewerId) {
                    $bid = (int)$row['id'];
                    $bs = $pdo->prepare('SELECT 1 FROM article_bookmarks WHERE user_id = ? AND article_id = ? LIMIT 1');
                    $bs->execute([(int)$viewerId, $bid]);
                    $extra['is_bookmarked'] = (bool)$bs->fetchColumn();
                }
                $items[] = artShapeArticle($row, $extra);
            }
            artJson(['articles' => $items, 'tab' => $tab, 'tag' => $tag ?: null]);
        }

        if ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                artJson(['error' => 'id required'], 400);
            }
            $trackView = !empty($_GET['track_view']);
            $article = artFetchArticle($pdo, $id, $viewerId ? (int)$viewerId : null, $trackView);
            if (!$article) {
                artJson(['error' => 'Статья не найдена'], 404);
            }
            if ($article['status'] !== 'published') {
                if (!$viewerId || (int)$article['author_id'] !== (int)$viewerId) {
                    artJson(['error' => 'Статья недоступна'], 403);
                }
            }
            artJson(['article' => $article]);
        }

        artJson(['error' => 'Unknown action'], 400);
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = trim($data['action'] ?? $action);

        if ($action === 'save_draft') {
            $id = (int)($data['id'] ?? 0);
            $title = trim((string)($data['title'] ?? ''));
            $content = artNormalizeMarkdownMedia((string)($data['content'] ?? ''));
            $coverRaw = trim((string)($data['cover_image'] ?? ''));
            $cover = $coverRaw !== '' ? (artStorageMediaPath($coverRaw) ?? $coverRaw) : '';
            $tags = artTagsString(artParseTags((string)($data['tags'] ?? '')));

            if ($id > 0) {
                $st = $pdo->prepare('SELECT author_id, status FROM articles WHERE id = ? LIMIT 1');
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    artJson(['error' => 'Статья не найдена'], 404);
                }
                if ((int)$row['author_id'] !== (int)$userId) {
                    artJson(['error' => 'Нет доступа'], 403);
                }
                $status = $row['status'] ?? 'draft';
                $pdo->prepare(
                    'UPDATE articles SET title = ?, content = ?, cover_image = ?, tags = ?, reading_time_min = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([
                    $title ?: 'Без названия',
                    $content,
                    $cover ?: null,
                    $tags ?: null,
                    artReadingTimeMin($content),
                    $id,
                ]);
            } else {
                $pdo->prepare(
                    "INSERT INTO articles (author_id, title, content, cover_image, tags, status, reading_time_min, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 'draft', ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())"
                )->execute([
                    $userId,
                    $title ?: 'Без названия',
                    $content,
                    $cover ?: null,
                    $tags ?: null,
                    artReadingTimeMin($content),
                ]);
                $id = (int)$pdo->lastInsertId();
            }

            $article = artFetchArticle($pdo, $id, (int)$userId, false);
            artJson(['success' => true, 'article' => $article]);
        }

        if ($action === 'publish') {
            $id = (int)($data['id'] ?? 0);
            if (!$id) {
                artJson(['error' => 'id required'], 400);
            }
            $st = $pdo->prepare('SELECT * FROM articles WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                artJson(['error' => 'Статья не найдена'], 404);
            }
            if ((int)$row['author_id'] !== (int)$userId) {
                artJson(['error' => 'Нет доступа'], 403);
            }
            $title = trim((string)($row['title'] ?? ''));
            if ($title === '' || $title === 'Без названия') {
                artJson(['error' => 'Укажите заголовок перед публикацией'], 400);
            }
            $content = trim((string)($row['content'] ?? ''));
            if ($content === '') {
                artJson(['error' => 'Добавьте текст статьи'], 400);
            }
            $reading = artReadingTimeMin($content);
            $wasPublished = ($row['status'] ?? '') === 'published';
            if ($wasPublished) {
                $pdo->prepare(
                    'UPDATE articles SET status = \'published\', reading_time_min = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([$reading, $id]);
            } else {
                $pdo->prepare(
                    'UPDATE articles SET status = \'published\', reading_time_min = ?, published_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([$reading, $id]);
            }
            $article = artFetchArticle($pdo, $id, (int)$userId, false);
            artJson(['success' => true, 'article' => $article, 'first_publish' => !$wasPublished]);
        }

        if ($action === 'bookmark') {
            $articleId = (int)($data['article_id'] ?? 0);
            if (!$articleId) {
                artJson(['error' => 'article_id required'], 400);
            }
            $st = $pdo->prepare("SELECT id, status FROM articles WHERE id = ? AND status = 'published' LIMIT 1");
            $st->execute([$articleId]);
            if (!$st->fetchColumn()) {
                artJson(['error' => 'Статья не найдена'], 404);
            }
            $chk = $pdo->prepare('SELECT 1 FROM article_bookmarks WHERE user_id = ? AND article_id = ? LIMIT 1');
            $chk->execute([(int)$userId, $articleId]);
            if ($chk->fetchColumn()) {
                $pdo->prepare('DELETE FROM article_bookmarks WHERE user_id = ? AND article_id = ?')
                    ->execute([(int)$userId, $articleId]);
                $pdo->prepare('UPDATE articles SET bookmarks_count = GREATEST(bookmarks_count - 1, 0) WHERE id = ?')
                    ->execute([$articleId]);
                $bookmarked = false;
            } else {
                $pdo->prepare('INSERT INTO article_bookmarks (user_id, article_id) VALUES (?, ?)')
                    ->execute([(int)$userId, $articleId]);
                $pdo->prepare('UPDATE articles SET bookmarks_count = bookmarks_count + 1 WHERE id = ?')
                    ->execute([$articleId]);
                $bookmarked = true;
            }
            $cnt = $pdo->prepare('SELECT bookmarks_count FROM articles WHERE id = ? LIMIT 1');
            $cnt->execute([$articleId]);
            artJson([
                'success'       => true,
                'bookmarked'    => $bookmarked,
                'bookmarks_count' => (int)$cnt->fetchColumn(),
            ]);
        }

        if ($action === 'react_useful') {
            $articleId = (int)($data['article_id'] ?? 0);
            if (!$articleId) {
                artJson(['error' => 'article_id required'], 400);
            }
            $st = $pdo->prepare("SELECT id FROM articles WHERE id = ? AND status = 'published' LIMIT 1");
            $st->execute([$articleId]);
            if (!$st->fetchColumn()) {
                artJson(['error' => 'Статья не найдена'], 404);
            }
            $chk = $pdo->prepare('SELECT 1 FROM article_reactions WHERE user_id = ? AND article_id = ? LIMIT 1');
            $chk->execute([(int)$userId, $articleId]);
            if ($chk->fetchColumn()) {
                $pdo->prepare('DELETE FROM article_reactions WHERE user_id = ? AND article_id = ?')
                    ->execute([(int)$userId, $articleId]);
                $pdo->prepare('UPDATE articles SET useful_count = GREATEST(useful_count - 1, 0) WHERE id = ?')
                    ->execute([$articleId]);
                $useful = false;
            } else {
                $pdo->prepare('INSERT INTO article_reactions (user_id, article_id) VALUES (?, ?)')
                    ->execute([(int)$userId, $articleId]);
                $pdo->prepare('UPDATE articles SET useful_count = useful_count + 1 WHERE id = ?')
                    ->execute([$articleId]);
                $useful = true;
            }
            $cnt = $pdo->prepare('SELECT useful_count FROM articles WHERE id = ? LIMIT 1');
            $cnt->execute([$articleId]);
            artJson([
                'success'      => true,
                'user_useful'  => $useful,
                'useful_count' => (int)$cnt->fetchColumn(),
            ]);
        }

        if ($action === 'delete') {
            $id = (int)($data['id'] ?? 0);
            if (!$id) {
                artJson(['error' => 'id required'], 400);
            }
            $st = $pdo->prepare('SELECT author_id FROM articles WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $authorId = (int)$st->fetchColumn();
            if (!$authorId) {
                artJson(['error' => 'Статья не найдена'], 404);
            }
            if ($authorId !== (int)$userId) {
                artJson(['error' => 'Только автор может удалить статью'], 403);
            }
            $pdo->prepare('DELETE FROM article_bookmarks WHERE article_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM article_reactions WHERE article_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM articles WHERE id = ? AND author_id = ?')->execute([$id, $userId]);
            artJson(['success' => true, 'deleted' => true]);
        }

        artJson(['error' => 'Unknown action'], 400);
    }

    artJson(['error' => 'Method not allowed'], 405);
} catch (PDOException $e) {
    artJson(['error' => 'DB: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    artJson(['error' => $e->getMessage()], 500);
}
