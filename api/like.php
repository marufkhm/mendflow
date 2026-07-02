<?php
/**
 * like.php — toggle like для постов пользователей и университетов
 *
 * POST /like.php
 * Body: {
 *   "postId":    <int>,
 *   "post_type": "user" | "university"   // default: "user"
 * }
 *
 * Возвращает: { liked: bool, likes_count: int }
 *
 * Разделение по post_type решает коллизию id:
 *   posts.id=5 и uni_posts.id=5 — разные записи.
 *   Без post_type лайк всегда шёл в таблицу likes по post_id=5,
 *   что было багом с потерей данных.
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

function likeJson($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') likeJson(['error' => 'Method not allowed'], 405);

require_once 'db.php';
require_once __DIR__ . '/feed_ranking.php';

$userId = verifyToken(); // бросает исключение / завершает скрипт если нет токена

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$postId   = (int)($input['postId'] ?? $input['post_id'] ?? 0);
$postType = in_array($input['post_type'] ?? '', ['university', 'company'], true) ? $input['post_type'] : 'user';

if (!$postId) likeJson(['error' => 'postId required'], 400);

try {
    /* ── Определяем таблицы в зависимости от типа поста ── */
    if ($postType === 'university') {
        // uni_posts — посты университетов и клубов
        $postsTable = 'uni_posts';
        $likesTable = 'uni_likes';

        // Создаём uni_likes если не существует (безопасная инициализация)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS uni_likes (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                post_id     INT NOT NULL,
                user_id     INT NOT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_uni_like (post_id, user_id),
                INDEX idx_uni_likes_post (post_id),
                INDEX idx_uni_likes_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } elseif ($postType === 'company') {
        $postsTable = 'company_posts';
        $likesTable = 'company_likes';
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS company_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_co_like (post_id, user_id),
                KEY idx_co_likes_post (post_id),
                KEY idx_co_likes_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } else {
        // posts — обычные пользовательские посты
        $postsTable = 'posts';
        $likesTable = 'likes';
    }

    // Проверяем что пост существует
    $st = $pdo->prepare("SELECT id FROM {$postsTable} WHERE id = ? LIMIT 1");
    $st->execute([$postId]);
    if (!$st->fetch()) likeJson(['error' => 'Post not found'], 404);

    // Текущий статус лайка
    $st = $pdo->prepare("SELECT id FROM {$likesTable} WHERE post_id = ? AND user_id = ? LIMIT 1");
    $st->execute([$postId, $userId]);
    $existingLike = $st->fetch();

    if ($existingLike) {
        // Убираем лайк
        $pdo->prepare("DELETE FROM {$likesTable} WHERE post_id = ? AND user_id = ?")
            ->execute([$postId, $userId]);
        $liked = false;
    } else {
        // Ставим лайк
        $pdo->prepare("INSERT IGNORE INTO {$likesTable} (post_id, user_id) VALUES (?, ?)")
            ->execute([$postId, $userId]);
        $liked = true;

        if ($postType === 'user') {
            try {
                $stAuthor = $pdo->prepare("SELECT user_id FROM posts WHERE id = ? LIMIT 1");
                $stAuthor->execute([$postId]);
                $authorId = (int)$stAuthor->fetchColumn();
                if ($authorId) {
                    frBumpAffinity($pdo, (int)$userId, $authorId, 'like');
                }
            } catch (Throwable $e) {}
        }

        // ── Уведомление автору поста (только при лайке, не при снятии) ──
        try {
            ensureNotificationsSchema($pdo);
            $stAuthor  = $pdo->prepare("SELECT user_id FROM {$postsTable} WHERE id = ? LIMIT 1");
            $stAuthor->execute([$postId]);
            $authorId = (int)$stAuthor->fetchColumn();

            if ($postType === 'company' && !$authorId) {
                $stAuthor = $pdo->prepare('SELECT user_id FROM company_profiles cp JOIN company_posts p ON p.company_id = cp.id WHERE p.id = ? LIMIT 1');
                $stAuthor->execute([$postId]);
                $authorId = (int)$stAuthor->fetchColumn();
            }

            if ($authorId && $authorId !== (int)$userId) {
                $textCol = $postsTable === 'uni_posts' || $postsTable === 'company_posts' ? 'text' : null;
                if (!$textCol) {
                    $cols    = $pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_COLUMN);
                    $textCol = in_array('text', $cols) ? 'text' : 'content';
                }
                $stPreview = $pdo->prepare("SELECT {$textCol} AS txt FROM {$postsTable} WHERE id = ? LIMIT 1");
                $stPreview->execute([$postId]);
                $preview   = mb_substr((string)$stPreview->fetchColumn(), 0, 60);

                $pdo->prepare("
                    INSERT INTO notifications (user_id, from_user_id, type, post_id, post_preview, post_type, created_at)
                    VALUES (?, ?, 'like', ?, ?, ?, NOW())
                ")->execute([$authorId, $userId, $postId, $preview, $postType]);
            }
        } catch (Throwable $e) {
            // Уведомления не критичны — продолжаем
        }
    }

    // Итоговый счётчик лайков
    $st = $pdo->prepare("SELECT COUNT(*) FROM {$likesTable} WHERE post_id = ?");
    $st->execute([$postId]);
    $likesCount = (int)$st->fetchColumn();

    likeJson(['liked' => $liked, 'likes_count' => $likesCount]);

} catch (Throwable $e) {
    likeJson(['error' => 'DB error: ' . $e->getMessage()], 500);
}
?>