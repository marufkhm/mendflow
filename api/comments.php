<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once __DIR__ . '/feed_ranking.php';

function commentsPostType(array $source = []): string
{
    $type = $source['post_type'] ?? $_GET['post_type'] ?? '';
    return $type === 'company' ? 'company' : 'user';
}

function ensureCompanyCommentsTable(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS company_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_co_comments_post (post_id),
            KEY idx_co_comments_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }
}

function commentsTableMeta(PDO $pdo, string $postType): array
{
    if ($postType === 'company') {
        ensureCompanyCommentsTable($pdo);
        return ['table' => 'company_comments', 'text' => 'text', 'posts' => 'company_posts'];
    }

    $cols = $pdo->query('SHOW COLUMNS FROM comments')->fetchAll(PDO::FETCH_COLUMN);
    return [
        'table' => 'comments',
        'text'  => in_array('text', $cols, true) ? 'text' : 'content',
        'posts' => 'posts',
    ];
}

try {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = verifyTokenSoft();
        $postId = (int)($_GET['postId'] ?? 0);
        if (!$postId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID поста обязателен']);
            exit;
        }

        $postType = commentsPostType($_GET);
        $meta = commentsTableMeta($pdo, $postType);
        $table = $meta['table'];
        $txtCol = $meta['text'];

        $stmt = $pdo->prepare("
            SELECT c.id, c.{$txtCol} AS content, c.created_at,
                   u.id AS user_id, u.first_name, u.last_name, u.avatar, u.is_verified
            FROM {$table} c JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ? ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll();
        foreach ($comments as &$c) {
            $c['created_at'] = utcDate($c['created_at']);
            $c['time_ago'] = timeAgo($c['created_at']);
        }
        echo json_encode(['comments' => $comments]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId  = verifyToken();
        $data    = json_decode(file_get_contents('php://input'), true);
        $postId  = (int)($data['postId'] ?? 0);
        $content = trim($data['content'] ?? '');

        if (!$postId || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID поста и текст комментария обязательны']);
            exit;
        }

        $postType = commentsPostType($data);
        $meta = commentsTableMeta($pdo, $postType);
        $table = $meta['table'];
        $txtCol = $meta['text'];
        $postsTable = $meta['posts'];

        $chk = $pdo->prepare("SELECT id FROM {$postsTable} WHERE id = ? LIMIT 1");
        $chk->execute([$postId]);
        if (!$chk->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Пост не найден']);
            exit;
        }

        $pdo->prepare("INSERT INTO {$table} (post_id, user_id, {$txtCol}) VALUES (?, ?, ?)")
            ->execute([$postId, $userId, $content]);
        $cid = $pdo->lastInsertId();

        try {
            if ($postType === 'company') {
                $authorStmt = $pdo->prepare(
                    'SELECT cp.user_id FROM company_profiles cp
                     JOIN company_posts p ON p.company_id = cp.id WHERE p.id = ? LIMIT 1'
                );
            } else {
                $authorStmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ? LIMIT 1');
            }
            $authorStmt->execute([$postId]);
            $authorId = (int)$authorStmt->fetchColumn();
            if ($authorId) {
                frBumpAffinity($pdo, (int)$userId, $authorId, 'comment');
            }
        } catch (Throwable $e) {
        }

        $stmt = $pdo->prepare("
            SELECT c.id, c.{$txtCol} AS content, c.created_at,
                   u.id AS user_id, u.first_name, u.last_name, u.avatar, u.is_verified
            FROM {$table} c JOIN users u ON c.user_id = u.id WHERE c.id = ?
        ");
        $stmt->execute([$cid]);
        $comment = $stmt->fetch();
        $comment['created_at'] = utcDate($comment['created_at']);
        $comment['time_ago'] = timeAgo($comment['created_at']);
        echo json_encode(['comment' => $comment]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $userId = verifyToken();
        $commentId = (int)($_GET['id'] ?? 0);
        $postType = commentsPostType($_GET);

        if (!$commentId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID комментария обязателен']);
            exit;
        }

        $meta = commentsTableMeta($pdo, $postType);
        $table = $meta['table'];

        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Нет прав или комментарий не найден']);
            exit;
        }

        echo json_encode(['success' => true, 'id' => $commentId]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешён']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
