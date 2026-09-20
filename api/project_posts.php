<?php
/**
 * MENDFLOW — project_posts.php
 * Sprint 2: Project Feed + Reactions + Activity
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET: list posts ────────────────────────────────────────
    if ($method === 'GET') {
        $userId    = verifyTokenSoft();
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) { http_response_code(400); echo json_encode(['error'=>'project_id required']); exit; }

        $page   = max(1,  (int)($_GET['page']  ?? 1));
        $limit  = min(30, (int)($_GET['limit'] ?? 10));
        $offset = ($page - 1) * $limit;
        $type   = $_GET['type'] ?? '';

        $where  = ['pp.project_id = ?'];
        $params = [$projectId];
        if ($type) { $where[] = 'pp.type = ?'; $params[] = $type; }
        $whereSQL = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT
                pp.id, pp.type, pp.title, pp.content, pp.image_url, pp.created_at,
                u.id AS author_id, u.first_name, u.last_name, u.avatar,
                (SELECT COUNT(*) FROM project_post_likes   l WHERE l.post_id = pp.id)                          AS likes_count,
                (SELECT COUNT(*) FROM project_post_comments c WHERE c.post_id = pp.id)                         AS comments_count,
                " . ($userId ? "(SELECT COUNT(*) FROM project_post_likes l2 WHERE l2.post_id=pp.id AND l2.user_id=$userId)" : "0") . " AS user_liked,
                " . ($userId ? "(SELECT emoji FROM project_post_reactions r WHERE r.post_id=pp.id AND r.user_id=$userId LIMIT 1)" : "NULL") . " AS user_reaction,
                (SELECT pm.permission_role FROM project_members pm WHERE pm.project_id=pp.project_id AND pm.user_id=u.id LIMIT 1) AS author_role
            FROM project_posts pp
            JOIN users u ON pp.author_id = u.id
            WHERE $whereSQL
            ORDER BY pp.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $posts = $stmt->fetchAll();

        // Attach reactions map per post
        foreach ($posts as &$p) {
            $p['user_liked'] = (bool)$p['user_liked'];
            $p['time_ago']   = timeAgo($p['created_at']);
            $p['reactions']  = getReactionsMap($pdo, $p['id']);
        }

        echo json_encode(['posts' => $posts]);
        exit;
    }

    // ── POST ──────────────────────────────────────────────────
    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? 'create';

        // ── Create post ──────────────────────────────────────
        if ($action === 'create') {
            $projectId = (int)($data['project_id'] ?? 0);
            $content   = trim($data['content'] ?? '');
            if (!$projectId || !$content) { http_response_code(400); echo json_encode(['error'=>'project_id и content обязательны']); exit; }
            requireMember($pdo, $projectId, $userId);

            $type  = in_array($data['type']??'', ['general','update','milestone','release','hiring']) ? $data['type'] : 'general';
            $title = trim($data['title'] ?? '') ?: null;

            $pdo->prepare("
                INSERT INTO project_posts (project_id, author_id, type, title, content, image_url)
                VALUES (?,?,?,?,?,?)
            ")->execute([$projectId, $userId, $type, $title, $content, $data['image_url']??null]);
            $postId = (int)$pdo->lastInsertId();

            // Auto-generate activity
            logActivity($pdo, $projectId, $userId, 'post', $title ?: mb_substr($content, 0, 60));

            $stmt = $pdo->prepare("
                SELECT pp.*, u.first_name, u.last_name, u.avatar,
                    (SELECT pm.permission_role FROM project_members pm WHERE pm.project_id=pp.project_id AND pm.user_id=u.id LIMIT 1) AS author_role
                FROM project_posts pp JOIN users u ON pp.author_id=u.id WHERE pp.id=?
            ");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            $post['likes_count']    = 0;
            $post['comments_count'] = 0;
            $post['user_liked']     = false;
            $post['user_reaction']  = null;
            $post['reactions']      = [];
            $post['time_ago']       = 'только что';

            echo json_encode(['post' => $post]);
            exit;
        }

        // ── Like / unlike ────────────────────────────────────
        if ($action === 'like') {
            $postId = (int)($data['post_id'] ?? 0);
            $check  = $pdo->prepare("SELECT id FROM project_post_likes WHERE post_id=? AND user_id=?");
            $check->execute([$postId, $userId]);
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM project_post_likes WHERE post_id=? AND user_id=?")->execute([$postId, $userId]);
                $liked = false;
            } else {
                $pdo->prepare("INSERT INTO project_post_likes (post_id, user_id) VALUES (?,?)")->execute([$postId, $userId]);
                $liked = true;
            }
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM project_post_likes WHERE post_id=?");
            $cnt->execute([$postId]);
            echo json_encode(['liked'=>$liked, 'count'=>(int)$cnt->fetchColumn()]);
            exit;
        }

        // ── Emoji reaction ───────────────────────────────────
        if ($action === 'react') {
            $postId = (int)($data['post_id'] ?? 0);
            $emoji  = mb_substr(trim($data['emoji'] ?? ''), 0, 10);
            if (!$postId || !$emoji) { http_response_code(400); echo json_encode(['error'=>'post_id и emoji обязательны']); exit; }

            // Check existing reaction by this user on this post
            $cur = $pdo->prepare("SELECT emoji FROM project_post_reactions WHERE post_id=? AND user_id=?");
            $cur->execute([$postId, $userId]);
            $existing = $cur->fetchColumn();

            if ($existing === $emoji) {
                // Toggle off same emoji
                $pdo->prepare("DELETE FROM project_post_reactions WHERE post_id=? AND user_id=?")->execute([$postId, $userId]);
                $userReaction = null;
            } else {
                // Replace or insert
                $pdo->prepare("DELETE FROM project_post_reactions WHERE post_id=? AND user_id=?")->execute([$postId, $userId]);
                $pdo->prepare("INSERT INTO project_post_reactions (post_id, user_id, emoji) VALUES (?,?,?)")->execute([$postId, $userId, $emoji]);
                $userReaction = $emoji;
            }

            echo json_encode([
                'reactions'     => getReactionsMap($pdo, $postId),
                'user_reaction' => $userReaction,
            ]);
            exit;
        }

        // ── Add comment ──────────────────────────────────────
        if ($action === 'comment') {
            $postId  = (int)($data['post_id'] ?? 0);
            $content = trim($data['content']  ?? '');
            if (!$postId || !$content) { http_response_code(400); echo json_encode(['error'=>'Пустой комментарий']); exit; }

            $pdo->prepare("INSERT INTO project_post_comments (post_id, author_id, content) VALUES (?,?,?)")->execute([$postId, $userId, $content]);
            $commentId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name, u.avatar FROM project_post_comments c JOIN users u ON c.author_id=u.id WHERE c.id=?");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch();
            $comment['time_ago'] = 'только что';
            echo json_encode(['comment' => $comment]);
            exit;
        }

        // ── Get comments ─────────────────────────────────────
        if ($action === 'get_comments') {
            $postId = (int)($data['post_id'] ?? 0);
            $stmt   = $pdo->prepare("
                SELECT c.*, u.first_name, u.last_name, u.avatar
                FROM project_post_comments c JOIN users u ON c.author_id=u.id
                WHERE c.post_id=? ORDER BY c.created_at ASC
            ");
            $stmt->execute([$postId]);
            $comments = $stmt->fetchAll();
            foreach ($comments as &$c) { $c['time_ago'] = timeAgo($c['created_at']); }
            echo json_encode(['comments' => $comments]);
            exit;
        }

        // ── Get activity ─────────────────────────────────────
        if ($action === 'get_activity') {
            $projectId = (int)($data['project_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT pa.*, u.first_name, u.last_name
                FROM project_activity pa
                LEFT JOIN users u ON pa.user_id = u.id
                WHERE pa.project_id=?
                ORDER BY pa.created_at DESC LIMIT 15
            ");
            $stmt->execute([$projectId]);
            $items = $stmt->fetchAll();
            foreach ($items as &$a) { $a['time_ago'] = timeAgo($a['created_at']); }
            echo json_encode(['activity' => $items]);
            exit;
        }

        http_response_code(400); echo json_encode(['error'=>'Неизвестный action']); exit;
    }

    // ── DELETE ────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $userId = verifyToken();
        $postId = (int)($_GET['id'] ?? 0);
        $stmt   = $pdo->prepare("SELECT author_id, project_id FROM project_posts WHERE id=?");
        $stmt->execute([$postId]);
        $post   = $stmt->fetch();
        if (!$post) { http_response_code(404); echo json_encode(['error'=>'Не найден']); exit; }
        if ((int)$post['author_id'] !== $userId) {
            requireRole($pdo, $post['project_id'], $userId, ['owner','admin']);
        }
        $pdo->prepare("DELETE FROM project_posts WHERE id=?")->execute([$postId]);
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Метод не разрешён']);

} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>'DB: '.$e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
}

// ── Helpers ─────────────────────────────────────────────────────
function getReactionsMap(PDO $pdo, int $postId): array {
    $stmt = $pdo->prepare("SELECT emoji, COUNT(*) AS cnt FROM project_post_reactions WHERE post_id=? GROUP BY emoji");
    $stmt->execute([$postId]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) { $map[$row['emoji']] = (int)$row['cnt']; }
    return $map;
}

function logActivity(PDO $pdo, int $projectId, int $userId, string $type, ?string $meta=null): void {
    try {
        $pdo->prepare("INSERT INTO project_activity (project_id, user_id, type, meta) VALUES (?,?,?,?)")
            ->execute([$projectId, $userId, $type, $meta]);
    } catch (Throwable $e) { /* non-critical */ }
}

function requireMember(PDO $pdo, int $projectId, int $userId): void {
    $stmt = $pdo->prepare("SELECT id FROM project_members WHERE project_id=? AND user_id=?");
    $stmt->execute([$projectId, $userId]);
    if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['error'=>'Только участники команды']); exit; }
}

function requireRole(PDO $pdo, int $projectId, int $userId, array $roles): void {
    $stmt = $pdo->prepare("SELECT permission_role FROM project_members WHERE project_id=? AND user_id=?");
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch();
    if (!$row || !in_array($row['permission_role'], $roles)) {
        http_response_code(403); echo json_encode(['error'=>'Нет прав']); exit;
    }
}