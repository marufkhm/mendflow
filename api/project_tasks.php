<?php
/**
 * MENDFLOW — project_tasks.php
 * Sprint 4: Full Kanban — assignee, filters, detail, comments
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

ensureTaskMilestoneColumn($pdo);
ensureTaskChecklistColumn($pdo);
ensureTaskDependsOnColumn($pdo);
ensureTaskAttachmentsTable($pdo);
ensureTaskTimeLogsTable($pdo);
ensureProjectKanbanColumnsSchema($pdo);

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET ───────────────────────────────────────────────────
    if ($method === 'GET') {
        $userId    = verifyToken();
        $projectId = (int)($_GET['project_id'] ?? 0);
        $action    = $_GET['action'] ?? '';
        $taskId    = (int)($_GET['task_id']    ?? 0);

        // Attachments list
        if ($action === 'attachments' && $taskId) {
            $stmt = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $taskRow = $stmt->fetch();
            if (!$taskRow) { http_response_code(404); echo json_encode(['error' => 'Задача не найдена']); exit; }
            requireAccess($pdo, (int)$taskRow['project_id'], $userId);
            echo json_encode(['attachments' => fetchTaskAttachments($pdo, $taskId)]);
            exit;
        }

        // Due-date reminders for current user's assigned tasks
        if ($action === 'check_due_dates' && $projectId) {
            requireMember($pdo, $projectId, $userId);
            $created = runTaskDueDateChecks($pdo, $projectId, $userId);
            echo json_encode(['checked' => true, 'created' => $created]);
            exit;
        }

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

        // Time logs for a task
        if ($action === 'time_logs' && $taskId) {
            $stmt = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $taskRow = $stmt->fetch();
            if (!$taskRow) { http_response_code(404); echo json_encode(['error' => 'Задача не найдена']); exit; }
            requireAccess($pdo, (int)$taskRow['project_id'], $userId);
            echo json_encode(['logs' => fetchTaskTimeLogs($pdo, $taskId)]);
            exit;
        }

        // Time summary for project overview
        if ($action === 'time_summary' && $projectId) {
            requireAccess($pdo, $projectId, $userId);
            echo json_encode(fetchProjectTimeSummary($pdo, $projectId));
            exit;
        }

        if (!$projectId) { http_response_code(400); echo json_encode(['error'=>'project_id required']); exit; }
        requireAccess($pdo, $projectId, $userId);

        $stmt = $pdo->prepare("
            SELECT
                t.id, t.title, t.description, t.status, t.priority,
                t.due_date, t.position, t.created_at, t.created_by,
                t.assignee_id, t.milestone_id, t.checklist, t.depends_on,
                (SELECT COUNT(*) FROM task_attachments ta WHERE ta.task_id = t.id) AS attachment_count,
                u.first_name  AS assignee_first,
                u.last_name   AS assignee_last,
                u.avatar      AS assignee_avatar,
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

        $kanbanColumns = fetchProjectKanbanColumns($pdo, $projectId);
        $columns = buildProjectTaskColumns($pdo, $projectId, array_map(function ($t) {
            $t['time_ago'] = timeAgo($t['created_at']);
            $t['checklist'] = parseTaskChecklist($t['checklist'] ?? null);
            $t['depends_on'] = parseTaskDependsOn($t['depends_on'] ?? null);
            $t['attachment_count'] = (int)($t['attachment_count'] ?? 0);
            return $t;
        }, $tasks));
        echo json_encode(['columns' => $columns, 'kanban_columns' => $kanbanColumns]);
        exit;
    }

    // ── POST ──────────────────────────────────────────────────
    if ($method === 'POST') {
        $userId = verifyToken();

        // Multipart: attach file to task
        if (!empty($_FILES['file']) && ($_POST['action'] ?? '') === 'attach_file') {
            handleTaskAttachFile($pdo, $userId);
            exit;
        }

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

            $status     = validateProjectTaskStatus($pdo, $projectId, (string)($data['status'] ?? 'backlog'));
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

            if ($assigneeId && $assigneeId !== $userId) {
                notifyTaskAssigned($pdo, $assigneeId, $userId, $taskId, $projectId, $title);
            }

            $stmt = $pdo->prepare("
                SELECT t.*,
                    u.first_name AS assignee_first, u.last_name AS assignee_last, u.avatar AS assignee_avatar,
                    cu.first_name AS creator_first, cu.last_name AS creator_last
                FROM project_tasks t
                LEFT JOIN users u  ON t.assignee_id=u.id
                LEFT JOIN users cu ON t.created_by=cu.id
                WHERE t.id=?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            $task['time_ago'] = 'только что';
            $task['checklist'] = parseTaskChecklist($task['checklist'] ?? null);
            $task['depends_on'] = parseTaskDependsOn($task['depends_on'] ?? null);
            logProjectActivity($pdo, $projectId, $userId, 'task_created', 'task', $taskId, [
                'title' => $title,
                'status' => $status,
            ]);
            echo json_encode(['task' => $task]);
            exit;
        }

        // Move
        if ($action === 'move') {
            $taskId    = (int)($data['task_id']  ?? 0);
            $newStatus = $data['status']          ?? '';
            $position  = (int)($data['position'] ?? 0);
            $stmt = $pdo->prepare("SELECT project_id, title, status, assignee_id, created_by FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) { http_response_code(404); echo json_encode(['error'=>'Не найдена']); exit; }
            requireMember($pdo, $task['project_id'], $userId);
            $oldStatus = (string)($task['status'] ?? '');
            $newStatus = validateProjectTaskStatus($pdo, (int)$task['project_id'], (string)$newStatus);
            $pdo->prepare("UPDATE project_tasks SET status=?,position=? WHERE id=?")->execute([$newStatus,$position,$taskId]);
            $pid = (int)$task['project_id'];
            if ($oldStatus !== $newStatus) {
                logProjectActivity($pdo, $pid, $userId, 'task_status_changed', 'task', $taskId, [
                    'title' => (string)($task['title'] ?? 'Задача'),
                    'status' => $newStatus,
                    'old_status' => $oldStatus,
                ]);
            }
            if ($newStatus === 'done' && $oldStatus !== 'done') {
                notifyTaskStatusDone($pdo, $task, $userId, $taskId, $pid);
            }
            echo json_encode(['success'=>true]);
            exit;
        }

        // Update
        if ($action === 'update') {
            $taskId = (int)($data['id'] ?? 0);
            $stmt   = $pdo->prepare("SELECT project_id, assignee_id, status, title, created_by, due_date FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) { http_response_code(404); echo json_encode(['error'=>'Не найдена']); exit; }
            requireMember($pdo, (int)$task['project_id'], $userId);
            $projectId = (int)$task['project_id'];
            $oldAssignee = (int)($task['assignee_id'] ?? 0);
            $oldStatus = (string)($task['status'] ?? '');
            $taskTitle = trim($data['title'] ?? $task['title'] ?? 'Задача');
            $assigneeId = ($data['assignee_id']??null) ? (int)$data['assignee_id'] : null;
            $newStatus = validateProjectTaskStatus($pdo, $projectId, (string)($data['status'] ?? $task['status'] ?? 'backlog'));
            $milestoneId = array_key_exists('milestone_id', $data)
                ? (($data['milestone_id'] ?? null) ? (int)$data['milestone_id'] : null)
                : null;
            $fields = 'title=?,description=?,status=?,priority=?,due_date=?,assignee_id=?';
            $params = [
                trim($data['title']??''), trim($data['description']??''),
                $newStatus, $data['priority']??'medium',
                $data['due_date']??null, $assigneeId,
            ];
            if (array_key_exists('milestone_id', $data)) {
                $fields .= ',milestone_id=?';
                $params[] = $milestoneId;
            }
            if (array_key_exists('depends_on', $data)) {
                $deps = validateTaskDependsOn($pdo, $taskId, $projectId, $data['depends_on'] ?? []);
                $fields .= ',depends_on=?';
                $params[] = $deps ? json_encode($deps) : null;
            }
            $oldDue = (string)($task['due_date'] ?? '');
            $newDue = $data['due_date'] ?? null;
            $params[] = $taskId;
            $pdo->prepare("UPDATE project_tasks SET {$fields} WHERE id=?")->execute($params);

            if ($assigneeId && $assigneeId !== $oldAssignee && $assigneeId !== $userId) {
                notifyTaskAssigned($pdo, $assigneeId, $userId, $taskId, $projectId, $taskTitle);
            }
            if ($newStatus === 'done' && $oldStatus !== 'done') {
                notifyTaskStatusDone($pdo, $task, $userId, $taskId, $projectId);
            }
            if ($oldStatus !== $newStatus) {
                logProjectActivity($pdo, $projectId, $userId, 'task_status_changed', 'task', $taskId, [
                    'title' => $taskTitle,
                    'status' => $newStatus,
                    'old_status' => $oldStatus,
                ]);
            } elseif ($assigneeId && $assigneeId !== $oldAssignee) {
                logProjectActivity($pdo, $projectId, $userId, 'task_assigned', 'task', $taskId, [
                    'title' => $taskTitle,
                    'assignee_id' => $assigneeId,
                ]);
            } elseif ($oldDue !== (string)($newDue ?? '')) {
                logProjectActivity($pdo, $projectId, $userId, 'task_due_changed', 'task', $taskId, [
                    'title' => $taskTitle,
                    'due_date' => $newDue,
                ]);
            } else {
                logProjectActivity($pdo, $projectId, $userId, 'task_updated', 'task', $taskId, [
                    'title' => $taskTitle,
                ]);
            }

            echo json_encode(['success'=>true]);
            exit;
        }

        // Delete attachment
        if ($action === 'delete_file') {
            $attachmentId = (int)($data['attachment_id'] ?? 0);
            if (!$attachmentId) {
                http_response_code(400); echo json_encode(['error' => 'attachment_id обязателен']); exit;
            }
            $stmt = $pdo->prepare("
                SELECT a.*, t.project_id
                FROM task_attachments a
                JOIN project_tasks t ON a.task_id = t.id
                WHERE a.id = ?
            ");
            $stmt->execute([$attachmentId]);
            $row = $stmt->fetch();
            if (!$row) { http_response_code(404); echo json_encode(['error' => 'Файл не найден']); exit; }
            requireMember($pdo, (int)$row['project_id'], $userId);
            deleteTaskAttachmentFile($row);
            $pdo->prepare("DELETE FROM task_attachments WHERE id=?")->execute([$attachmentId]);
            echo json_encode(['success' => true]);
            exit;
        }

        // Comment on task
        if ($action === 'comment') {
            $taskId  = (int)($data['task_id'] ?? 0);
            $content = trim($data['content']  ?? '');
            if (!$taskId || !$content) {
                http_response_code(400); echo json_encode(['error'=>'task_id и content обязательны']); exit;
            }
            $stmt = $pdo->prepare("SELECT project_id, title, assignee_id, created_by FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) { http_response_code(404); echo json_encode(['error'=>'Задача не найдена']); exit; }
            $projectId = (int)$task['project_id'];
            requireMember($pdo, $projectId, $userId);
            ensureTaskCommentsTable($pdo);
            $pdo->prepare("INSERT INTO task_comments (task_id,author_id,content) VALUES (?,?,?)")
                ->execute([$taskId, $userId, $content]);
            $commentId = (int)$pdo->lastInsertId();

            $preview = (string)($task['title'] ?? 'Задача');
            $postType = 'project_' . $projectId;
            $stakeholders = fetchTaskStakeholderIds($pdo, $taskId, (int)($task['assignee_id'] ?? 0), (int)($task['created_by'] ?? 0));
            foreach ($stakeholders as $uid) {
                if ($uid === $userId) continue;
                insertAppNotification($pdo, $uid, $userId, 'task_commented', $taskId, $preview, $postType);
            }
            foreach (parseAtMentions($pdo, $projectId, $content) as $uid) {
                if ($uid === $userId) continue;
                insertAppNotification($pdo, $uid, $userId, 'task_mentioned', $taskId, $preview, $postType);
            }
            logProjectActivity($pdo, $projectId, $userId, 'task_commented', 'task', $taskId, [
                'title' => $preview,
            ]);

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

        // Checklist
        if (in_array($action, ['checklist_add', 'checklist_toggle', 'checklist_delete'], true)) {
            $taskId = (int)($data['task_id'] ?? 0);
            if (!$taskId) {
                http_response_code(400); echo json_encode(['error' => 'task_id обязателен']); exit;
            }
            $stmt = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $taskRow = $stmt->fetch();
            if (!$taskRow) { http_response_code(404); echo json_encode(['error' => 'Задача не найдена']); exit; }
            requireMember($pdo, (int)$taskRow['project_id'], $userId);

            $items = getTaskChecklistItems($pdo, $taskId);

            if ($action === 'checklist_add') {
                $text = trim($data['text'] ?? '');
                if ($text === '') {
                    http_response_code(400); echo json_encode(['error' => 'Текст пункта обязателен']); exit;
                }
                if (mb_strlen($text) > 500) {
                    http_response_code(400); echo json_encode(['error' => 'Пункт слишком длинный']); exit;
                }
                $items[] = ['id' => checklistNewId(), 'text' => $text, 'done' => false];
            } elseif ($action === 'checklist_toggle') {
                $itemId = (string)($data['item_id'] ?? '');
                $done   = !empty($data['done']);
                $found  = false;
                foreach ($items as &$item) {
                    if ((string)$item['id'] === $itemId) {
                        $item['done'] = $done;
                        $found = true;
                        break;
                    }
                }
                unset($item);
                if (!$found) { http_response_code(404); echo json_encode(['error' => 'Пункт не найден']); exit; }
            } elseif ($action === 'checklist_delete') {
                $itemId = (string)($data['item_id'] ?? '');
                $before = count($items);
                $items = array_values(array_filter($items, fn($item) => (string)$item['id'] !== $itemId));
                if (count($items) === $before) {
                    http_response_code(404); echo json_encode(['error' => 'Пункт не найден']); exit;
                }
            }

            saveTaskChecklistItems($pdo, $taskId, $items);
            echo json_encode(['checklist' => $items]);
            exit;
        }

        // Time log entry
        if ($action === 'time_log') {
            $taskId = (int)($data['task_id'] ?? 0);
            $minutes = (int)($data['minutes'] ?? 0);
            $note = trim($data['note'] ?? '');
            if (!$taskId || $minutes < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'task_id и minutes (≥1) обязательны']);
                exit;
            }
            if ($minutes > 1440) {
                http_response_code(400);
                echo json_encode(['error' => 'Не более 24 часов за одну запись']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT project_id, title FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) { http_response_code(404); echo json_encode(['error' => 'Задача не найдена']); exit; }
            $projectId = (int)$task['project_id'];
            requireMember($pdo, $projectId, $userId);
            $loggedAt = trim($data['logged_at'] ?? '');
            if ($loggedAt === '') {
                $loggedAt = date('Y-m-d H:i:s');
            }
            $pdo->prepare("
                INSERT INTO task_time_logs (task_id, project_id, user_id, minutes, note, logged_at)
                VALUES (?,?,?,?,?,?)
            ")->execute([
                $taskId, $projectId, $userId, $minutes,
                $note !== '' ? mb_substr($note, 0, 500) : null,
                $loggedAt,
            ]);
            $logId = (int)$pdo->lastInsertId();
            logProjectActivity($pdo, $projectId, $userId, 'time_logged', 'task', $taskId, [
                'title' => (string)($task['title'] ?? 'Задача'),
                'minutes' => $minutes,
            ]);
            $stmt = $pdo->prepare("
                SELECT tl.*, u.first_name, u.last_name
                FROM task_time_logs tl
                JOIN users u ON u.id = tl.user_id
                WHERE tl.id = ?
            ");
            $stmt->execute([$logId]);
            $row = $stmt->fetch();
            $row['time_ago'] = timeAgo($row['logged_at']);
            echo json_encode(['log' => $row]);
            exit;
        }

        http_response_code(400); echo json_encode(['error'=>'Неизвестный action']); exit;
    }

    // ── DELETE ────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $userId = verifyToken();
        $taskId = (int)($_GET['id'] ?? 0);
        if (!$taskId) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
        $stmt = $pdo->prepare("SELECT project_id, created_by, title FROM project_tasks WHERE id=?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if (!$task) { http_response_code(404); echo json_encode(['error'=>'Не найдена']); exit; }
        if ((int)$task['created_by'] !== $userId) requireRole($pdo, $task['project_id'], $userId, ['owner','admin']);
        logProjectActivity($pdo, (int)$task['project_id'], $userId, 'task_deleted', 'task', $taskId, [
            'title' => (string)($task['title'] ?? 'Задача'),
        ]);
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
function ensureTaskChecklistColumn(PDO $pdo): void {
    $col = $pdo->query("SHOW COLUMNS FROM project_tasks LIKE 'checklist'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE project_tasks ADD COLUMN checklist JSON DEFAULT NULL AFTER description");
    }
}
function parseTaskChecklist($raw): array {
    if ($raw === null || $raw === '') return [];
    if (is_array($raw)) return normalizeChecklistItems($raw);
    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? normalizeChecklistItems($decoded) : [];
}
function normalizeChecklistItems(array $items): array {
    $out = [];
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $text = trim((string)($item['text'] ?? ''));
        if ($text === '') continue;
        $out[] = [
            'id'   => (string)($item['id'] ?? checklistNewId()),
            'text' => $text,
            'done' => !empty($item['done']),
        ];
    }
    return $out;
}
function checklistNewId(): string {
    return bin2hex(random_bytes(8));
}
function getTaskChecklistItems(PDO $pdo, int $taskId): array {
    $stmt = $pdo->prepare("SELECT checklist FROM project_tasks WHERE id=?");
    $stmt->execute([$taskId]);
    return parseTaskChecklist($stmt->fetchColumn());
}
function saveTaskChecklistItems(PDO $pdo, int $taskId, array $items): void {
    $items = normalizeChecklistItems($items);
    $json  = $items ? json_encode($items, JSON_UNESCAPED_UNICODE) : null;
    $pdo->prepare("UPDATE project_tasks SET checklist=? WHERE id=?")->execute([$json, $taskId]);
}
function ensureTaskDependsOnColumn(PDO $pdo): void {
    $col = $pdo->query("SHOW COLUMNS FROM project_tasks LIKE 'depends_on'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE project_tasks ADD COLUMN depends_on JSON DEFAULT NULL AFTER checklist");
    }
}
function parseTaskDependsOn($raw): array {
    if ($raw === null || $raw === '') return [];
    if (is_array($raw)) $ids = $raw;
    else {
        $decoded = json_decode((string)$raw, true);
        $ids = is_array($decoded) ? $decoded : [];
    }
    $out = [];
    foreach ($ids as $id) {
        $n = (int)$id;
        if ($n > 0) $out[] = $n;
    }
    return array_values(array_unique($out));
}
function validateTaskDependsOn(PDO $pdo, int $taskId, int $projectId, $raw): array {
    $deps = parseTaskDependsOn($raw);
    $deps = array_values(array_filter($deps, fn($id) => $id !== $taskId));
    foreach ($deps as $depId) {
        $stmt = $pdo->prepare("SELECT id FROM project_tasks WHERE id=? AND project_id=?");
        $stmt->execute([$depId, $projectId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Зависимость указывает на задачу из другого проекта']);
            exit;
        }
    }
    if (taskDependsOnCreatesCycle($pdo, $projectId, $taskId, $deps)) {
        http_response_code(400);
        echo json_encode(['error' => 'Циклическая зависимость между задачами']);
        exit;
    }
    return $deps;
}
function taskDependsOnCreatesCycle(PDO $pdo, int $projectId, int $taskId, array $newDeps): bool {
    $graph = [];
    $stmt = $pdo->prepare("SELECT id, depends_on FROM project_tasks WHERE project_id=?");
    $stmt->execute([$projectId]);
    while ($row = $stmt->fetch()) {
        $id = (int)$row['id'];
        $graph[$id] = ($id === $taskId) ? $newDeps : parseTaskDependsOn($row['depends_on'] ?? null);
    }
    if (!isset($graph[$taskId])) $graph[$taskId] = $newDeps;
    $visited = [];
    $stack = [];
    $visit = function (int $node) use (&$visit, &$graph, &$visited, &$stack): bool {
        if (isset($stack[$node])) return true;
        if (isset($visited[$node])) return false;
        $visited[$node] = true;
        $stack[$node] = true;
        foreach ($graph[$node] ?? [] as $dep) {
            if ($visit((int)$dep)) return true;
        }
        unset($stack[$node]);
        return false;
    };
    foreach (array_keys($graph) as $node) {
        if ($visit((int)$node)) return true;
    }
    return false;
}
function ensureTaskAttachmentsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS task_attachments (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        task_id       INT NOT NULL,
        project_id    INT NOT NULL,
        uploaded_by   INT NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name   VARCHAR(255) NOT NULL,
        mime_type     VARCHAR(120) NOT NULL,
        size_bytes    INT UNSIGNED NOT NULL DEFAULT 0,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_task_attachments_task (task_id),
        FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
    )");
}
function fetchTaskAttachments(PDO $pdo, int $taskId): array {
    $stmt = $pdo->prepare("
        SELECT a.*, u.first_name, u.last_name
        FROM task_attachments a
        JOIN users u ON a.uploaded_by = u.id
        WHERE a.task_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$taskId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['url'] = mfUploadPublicUrl($r['stored_name']);
        $r['time_ago'] = timeAgo($r['created_at']);
    }
    return $rows;
}
function handleTaskAttachFile(PDO $pdo, int $userId): void {
    $taskId = (int)($_POST['task_id'] ?? 0);
    if (!$taskId) {
        http_response_code(400); echo json_encode(['error' => 'task_id обязателен']); exit;
    }
    $stmt = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) { http_response_code(404); echo json_encode(['error' => 'Задача не найдена']); exit; }
    $projectId = (int)$task['project_id'];
    requireMember($pdo, $projectId, $userId);

    $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed',
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
    ];
    $maxBytes = 25 * 1024 * 1024;
    $check = validateUploadedFile($_FILES['file'], $allowedMimes, $maxBytes);
    if (isset($check['error'])) {
        http_response_code(400); echo json_encode(['error' => $check['error']]); exit;
    }

    $original = basename((string)($_FILES['file']['name'] ?? 'file'));
    $original = mb_substr($original, 0, 255);
    $mime = normalizeUploadMime($check['mime']);
    $ext = safeExtFromMime($mime);
    if ($ext === 'bin' && preg_match('/\.([a-z0-9]{1,8})$/i', $original, $m)) {
        $ext = strtolower($m[1]);
    }
    $stored = 'taskatt_' . $userId . '_' . mfUniqueUploadToken() . '.' . $ext;
    $uploadDir = mfUploadsDir();
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
        http_response_code(500); echo json_encode(['error' => 'Папка uploads недоступна']); exit;
    }
    $dest = $uploadDir . $stored;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        http_response_code(500); echo json_encode(['error' => 'Не удалось сохранить файл']); exit;
    }

    $size = (int)filesize($dest);
    $pdo->prepare("
        INSERT INTO task_attachments (task_id, project_id, uploaded_by, original_name, stored_name, mime_type, size_bytes)
        VALUES (?,?,?,?,?,?,?)
    ")->execute([$taskId, $projectId, $userId, $original, $stored, $mime, $size]);

    $attachmentId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT a.*, u.first_name, u.last_name
        FROM task_attachments a JOIN users u ON a.uploaded_by = u.id WHERE a.id=?
    ");
    $stmt->execute([$attachmentId]);
    $row = $stmt->fetch();
    $row['url'] = mfUploadPublicUrl($row['stored_name']);
    $row['time_ago'] = 'только что';
    echo json_encode(['attachment' => $row]);
}
function deleteTaskAttachmentFile(array $row): void {
    $path = mfUploadsDir() . ($row['stored_name'] ?? '');
    if ($path && is_file($path)) @unlink($path);
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

function taskPostType(int $projectId): string
{
    return 'project_' . $projectId;
}

function notifyTaskAssigned(PDO $pdo, int $assigneeId, int $fromUserId, int $taskId, int $projectId, string $title): void
{
    insertAppNotification($pdo, $assigneeId, $fromUserId, 'task_assigned', $taskId, $title, taskPostType($projectId));
}

function notifyTaskStatusDone(PDO $pdo, array $task, int $actorId, int $taskId, int $projectId): void
{
    $preview = (string)($task['title'] ?? 'Задача');
    $postType = taskPostType($projectId);
    foreach ([(int)($task['assignee_id'] ?? 0), (int)($task['created_by'] ?? 0)] as $uid) {
        if ($uid <= 0 || $uid === $actorId) continue;
        insertAppNotification($pdo, $uid, $actorId, 'task_status_changed', $taskId, $preview, $postType);
    }
}

function fetchTaskStakeholderIds(PDO $pdo, int $taskId, int $assigneeId, int $createdBy): array
{
    ensureTaskCommentsTable($pdo);
    $ids = [];
    if ($assigneeId > 0) $ids[$assigneeId] = true;
    if ($createdBy > 0) $ids[$createdBy] = true;
    $stmt = $pdo->prepare("SELECT DISTINCT author_id FROM task_comments WHERE task_id = ?");
    $stmt->execute([$taskId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        $uid = (int)$uid;
        if ($uid > 0) $ids[$uid] = true;
    }
    return array_map('intval', array_keys($ids));
}

function runTaskDueDateChecks(PDO $pdo, int $projectId, int $userId): int
{
    $created = 0;
    $postType = taskPostType($projectId);
    $tomorrow = (new DateTime('tomorrow'))->format('Y-m-d');
    $today = (new DateTime('today'))->format('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT id, title, due_date, status
        FROM project_tasks
        WHERE project_id = ? AND assignee_id = ? AND due_date IS NOT NULL
          AND status NOT IN ('done')
    ");
    $stmt->execute([$projectId, $userId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $taskId = (int)$row['id'];
        $title = (string)($row['title'] ?? 'Задача');
        $due = substr((string)($row['due_date'] ?? ''), 0, 10);
        if ($due === '') continue;

        if ($due === $tomorrow) {
            $before = appNotifSentToday($pdo, $userId, 'task_due_soon', $taskId);
            insertAppNotification($pdo, $userId, null, 'task_due_soon', $taskId, $title, $postType, true);
            if (!$before && appNotifSentToday($pdo, $userId, 'task_due_soon', $taskId)) $created++;
        } elseif ($due < $today) {
            $before = appNotifSentToday($pdo, $userId, 'task_overdue', $taskId);
            insertAppNotification($pdo, $userId, null, 'task_overdue', $taskId, $title, $postType, true);
            if (!$before && appNotifSentToday($pdo, $userId, 'task_overdue', $taskId)) $created++;
        }
    }
    return $created;
}

function fetchTaskTimeLogs(PDO $pdo, int $taskId): array
{
    ensureTaskTimeLogsTable($pdo);
    $stmt = $pdo->prepare("
        SELECT tl.*, u.first_name, u.last_name
        FROM task_time_logs tl
        JOIN users u ON u.id = tl.user_id
        WHERE tl.task_id = ?
        ORDER BY tl.logged_at DESC
        LIMIT 50
    ");
    $stmt->execute([$taskId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['time_ago'] = timeAgo($r['logged_at']);
    }
    return $rows;
}

function fetchProjectTimeSummary(PDO $pdo, int $projectId): array
{
    ensureTaskTimeLogsTable($pdo);
    $byMember = [];
    $stmt = $pdo->prepare("
        SELECT u.id AS user_id, u.first_name, u.last_name, u.avatar, SUM(tl.minutes) AS total_minutes
        FROM task_time_logs tl
        JOIN users u ON u.id = tl.user_id
        WHERE tl.project_id = ?
        GROUP BY u.id, u.first_name, u.last_name, u.avatar
        ORDER BY total_minutes DESC
    ");
    $stmt->execute([$projectId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byMember[] = [
            'user_id' => (int)$row['user_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'avatar' => $row['avatar'] ?? null,
            'total_minutes' => (int)$row['total_minutes'],
        ];
    }

    $byWeek = [];
    $stmt = $pdo->prepare("
        SELECT DATE(DATE_SUB(tl.logged_at, INTERVAL WEEKDAY(tl.logged_at) DAY)) AS week_start,
               SUM(tl.minutes) AS total_minutes
        FROM task_time_logs tl
        WHERE tl.project_id = ? AND tl.logged_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
        GROUP BY week_start
        ORDER BY week_start ASC
    ");
    $stmt->execute([$projectId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byWeek[] = [
            'week_start' => $row['week_start'],
            'total_minutes' => (int)$row['total_minutes'],
        ];
    }

    $total = array_sum(array_column($byMember, 'total_minutes'));
    return [
        'total_minutes' => $total,
        'by_member' => $byMember,
        'by_week' => $byWeek,
    ];
}