<?php
/**
 * MENDFLOW — project_tasks.php
 * Sprint 4: Full Kanban — assignee, filters, detail, comments
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

ensureTaskMilestoneColumn($pdo);

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET ───────────────────────────────────────────────────
    if ($method === 'GET') {
        $userId    = verifyToken();
        $projectId = (int)($_GET['project_id'] ?? 0);
        $action    = $_GET['action'] ?? '';
        $taskId    = (int)($_GET['task_id']    ?? 0);

        // Comments for a task
        if ($action === 'comments' && $taskId) {
            ensureTaskCommentsTable($pdo);
            $stmt = $pdo->prepare("
                SELECT tc.*, u.first_name, u.last_name, u.avatar
                FROM task_comments tc
                JOIN users u ON tc.author_id = u.id
                WHERE tc.task_id = ?
                ORDER BY tc.created_at ASC
            ");
            $stmt->execute([$taskId]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) { $r['time_ago'] = timeAgo($r['created_at']); }
            echo json_encode(['comments' => $rows]);
            exit;
        }

        if (!$projectId) { http_response_code(400); echo json_encode(['error'=>'project_id required']); exit; }
        requireAccess($pdo, $projectId, $userId);

        $stmt = $pdo->prepare("
            SELECT
                t.id, t.title, t.description, t.status, t.priority,
                t.due_date, t.position, t.created_at, t.created_by,
                t.assignee_id, t.milestone_id,
                u.first_name  AS assignee_first,
                u.last_name   AS assignee_last,
                cu.first_name AS creator_first,
                cu.last_name  AS creator_last
            FROM project_tasks t
            LEFT JOIN users u  ON t.assignee_id = u.id
            LEFT JOIN users cu ON t.created_by   = cu.id
            WHERE t.project_id = ?
            ORDER BY t.status, t.position ASC, t.created_at DESC
        ");
        $stmt->execute([$projectId]);
        $tasks = $stmt->fetchAll();

        $columns = ['backlog'=>[],'todo'=>[],'in_progress'=>[],'review'=>[],'done'=>[]];
        foreach ($tasks as $t) {
            $t['time_ago'] = timeAgo($t['created_at']);
            $columns[$t['status']][] = $t;
        }
        echo json_encode(['columns' => $columns]);
        exit;
    }

    // ── POST ──────────────────────────────────────────────────
    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? 'create';

        // Create
        if ($action === 'create') {
            $projectId = (int)($data['project_id'] ?? 0);
            $title     = trim($data['title'] ?? '');
            if (!$projectId || !$title) {
                http_response_code(400); echo json_encode(['error'=>'project_id и title обязательны']); exit;
            }
            requireMember($pdo, $projectId, $userId);

            $status     = $data['status']   ?? 'backlog';
            $assigneeId = ($data['assignee_id'] ?? null) ? (int)$data['assignee_id'] : null;
            $milestoneId = ($data['milestone_id'] ?? null) ? (int)$data['milestone_id'] : null;
            $maxPos     = $pdo->prepare("SELECT COALESCE(MAX(position),0)+1 FROM project_tasks WHERE project_id=? AND status=?");
            $maxPos->execute([$projectId, $status]);
            $pos = (int)$maxPos->fetchColumn();

            $pdo->prepare("
                INSERT INTO project_tasks (project_id,milestone_id,created_by,assignee_id,title,description,status,priority,due_date,position)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $projectId, $milestoneId, $userId, $assigneeId,
                $title, trim($data['description']??''), $status,
                $data['priority']??'medium', $data['due_date']??null, $pos
            ]);
            $taskId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("
                SELECT t.*,
                    u.first_name AS assignee_first, u.last_name AS assignee_last,
                    cu.first_name AS creator_first, cu.last_name AS creator_last
                FROM project_tasks t
                LEFT JOIN users u  ON t.assignee_id=u.id
                LEFT JOIN users cu ON t.created_by=cu.id
                WHERE t.id=?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            $task['time_ago'] = 'только что';
            echo json_encode(['task' => $task]);
            exit;
        }

        // Move
        if ($action === 'move') {
            $taskId    = (int)($data['task_id']  ?? 0);
            $newStatus = $data['status']          ?? '';
            $position  = (int)($data['position'] ?? 0);
            $stmt = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) { http_response_code(404); echo json_encode(['error'=>'Не найдена']); exit; }
            requireMember($pdo, $task['project_id'], $userId);
            $pdo->prepare("UPDATE project_tasks SET status=?,position=? WHERE id=?")->execute([$newStatus,$position,$taskId]);
            echo json_encode(['success'=>true]);
            exit;
        }

        // Update
        if ($action === 'update') {
            $taskId = (int)($data['id'] ?? 0);
            $stmt   = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) { http_response_code(404); echo json_encode(['error'=>'Не найдена']); exit; }
            requireMember($pdo, $task['project_id'], $userId);
            $assigneeId = ($data['assignee_id']??null) ? (int)$data['assignee_id'] : null;
            $milestoneId = array_key_exists('milestone_id', $data)
                ? (($data['milestone_id'] ?? null) ? (int)$data['milestone_id'] : null)
                : null;
            $fields = 'title=?,description=?,status=?,priority=?,due_date=?,assignee_id=?';
            $params = [
                trim($data['title']??''), trim($data['description']??''),
                $data['status']??'backlog', $data['priority']??'medium',
                $data['due_date']??null, $assigneeId,
            ];
            if (array_key_exists('milestone_id', $data)) {
                $fields .= ',milestone_id=?';
                $params[] = $milestoneId;
            }
            $params[] = $taskId;
            $pdo->prepare("UPDATE project_tasks SET {$fields} WHERE id=?")->execute($params);
            echo json_encode(['success'=>true]);
            exit;
        }

        // Comment on task
        if ($action === 'comment') {
            $taskId  = (int)($data['task_id'] ?? 0);
            $content = trim($data['content']  ?? '');
            if (!$taskId || !$content) {
                http_response_code(400); echo json_encode(['error'=>'task_id и content обязательны']); exit;
            }
            $stmt = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) { http_response_code(404); echo json_encode(['error'=>'Задача не найдена']); exit; }
            requireMember($pdo, $task['project_id'], $userId);
            ensureTaskCommentsTable($pdo);
            $pdo->prepare("INSERT INTO task_comments (task_id,author_id,content) VALUES (?,?,?)")
                ->execute([$taskId, $userId, $content]);
            $commentId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare("
                SELECT tc.*, u.first_name, u.last_name, u.avatar
                FROM task_comments tc JOIN users u ON tc.author_id=u.id WHERE tc.id=?
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch();
            $comment['time_ago'] = 'только что';
            echo json_encode(['comment' => $comment]);
            exit;
        }

        http_response_code(400); echo json_encode(['error'=>'Неизвестный action']); exit;
    }

    // ── DELETE ────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $userId = verifyToken();
        $taskId = (int)($_GET['id'] ?? 0);
        if (!$taskId) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
        $stmt = $pdo->prepare("SELECT project_id,created_by FROM project_tasks WHERE id=?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if (!$task) { http_response_code(404); echo json_encode(['error'=>'Не найдена']); exit; }
        if ((int)$task['created_by'] !== $userId) requireRole($pdo, $task['project_id'], $userId, ['owner','admin']);
        $pdo->prepare("DELETE FROM project_tasks WHERE id=?")->execute([$taskId]);
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Метод не разрешён']);

} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>'DB: '.$e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
}

function ensureTaskCommentsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS task_comments (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        task_id    INT NOT NULL,
        author_id  INT NOT NULL,
        content    TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id)   REFERENCES project_tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}
function ensureTaskMilestoneColumn(PDO $pdo): void {
    $col = $pdo->query("SHOW COLUMNS FROM project_tasks LIKE 'milestone_id'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE project_tasks ADD COLUMN milestone_id INT DEFAULT NULL AFTER project_id");
    }
}
function requireAccess(PDO $pdo, int $projectId, int $userId): void {
    $stmt = $pdo->prepare("SELECT is_public FROM projects WHERE id=?");
    $stmt->execute([$projectId]);
    $p = $stmt->fetch();
    if (!$p) { http_response_code(404); echo json_encode(['error'=>'Проект не найден']); exit; }
    if (!$p['is_public']) requireMember($pdo, $projectId, $userId);
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