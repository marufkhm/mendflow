<?php
/**
 * project_milestones.php — Roadmap / вехи проекта
 * GET  ?project_id= — список вех + задачи
 * POST { action: update|complete|link_task|unlink_task, ... }
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

const ROADMAP_STAGES = [
    ['stage_key' => 'idea',   'title' => 'Идея',   'position' => 1],
    ['stage_key' => 'mvp',    'title' => 'MVP',    'position' => 2],
    ['stage_key' => 'beta',   'title' => 'Beta',   'position' => 3],
    ['stage_key' => 'launch', 'title' => 'Запуск', 'position' => 4],
];

try {
    ensureRoadmapSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $userId    = verifyToken();
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) {
            http_response_code(400);
            echo json_encode(['error' => 'project_id required']);
            exit;
        }
        requireRoadmapAccess($pdo, $projectId, $userId);
        seedDefaultMilestones($pdo, $projectId);

        $stmt = $pdo->prepare("
            SELECT id, project_id, stage_key, title, description, target_date, start_date,
                   status, position, completed_at, created_at, updated_at
            FROM project_milestones
            WHERE project_id = ?
            ORDER BY position ASC
        ");
        $stmt->execute([$projectId]);
        $milestones = $stmt->fetchAll();

        $taskStmt = $pdo->prepare("
            SELECT id, title, status, priority, due_date, milestone_id
            FROM project_tasks
            WHERE project_id = ?
            ORDER BY status, position ASC, created_at DESC
        ");
        $taskStmt->execute([$projectId]);
        $allTasks = $taskStmt->fetchAll();

        $byMilestone = [];
        $unlinked    = [];
        foreach ($allTasks as $t) {
            $mid = $t['milestone_id'] ? (int)$t['milestone_id'] : 0;
            if ($mid) {
                $byMilestone[$mid][] = $t;
            } else {
                $unlinked[] = $t;
            }
        }

        foreach ($milestones as &$m) {
            $tasks = $byMilestone[(int)$m['id']] ?? [];
            $total = count($tasks);
            $done  = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
            $m['task_total'] = $total;
            $m['task_done']  = $done;
            $m['task_pct']   = $total > 0 ? (int)round($done / $total * 100) : 0;
            $m['tasks']      = $tasks;
        }
        unset($m);

        $proj = $pdo->prepare("SELECT stage FROM projects WHERE id=?");
        $proj->execute([$projectId]);
        $stage = $proj->fetchColumn() ?: 'idea';

        echo json_encode([
            'milestones'      => $milestones,
            'unlinked_tasks'  => $unlinked,
            'project_stage'   => $stage,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? '';

        if ($action === 'update') {
            $id = (int)($data['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'id required']);
                exit;
            }
            $m = fetchMilestone($pdo, $id);
            requireRoadmapMember($pdo, (int)$m['project_id'], $userId);

            $status = $data['status'] ?? $m['status'];
            if (!in_array($status, ['pending', 'in_progress', 'done'], true)) {
                $status = $m['status'];
            }
            $completedAt = $status === 'done' ? date('Y-m-d H:i:s') : null;
            if ($status !== 'done') {
                $completedAt = null;
            }

            $startDate = array_key_exists('start_date', $data)
                ? ($data['start_date'] ?: null)
                : ($m['start_date'] ?? null);

            $pdo->prepare("
                UPDATE project_milestones
                SET title=?, description=?, target_date=?, start_date=?, status=?, completed_at=?
                WHERE id=?
            ")->execute([
                trim($data['title'] ?? $m['title']),
                trim($data['description'] ?? $m['description'] ?? ''),
                $data['target_date'] ?? null,
                $startDate,
                $status,
                $completedAt,
                $id,
            ]);

            if ($status === 'in_progress' || $status === 'done') {
                syncProjectStage($pdo, (int)$m['project_id'], $m['stage_key'], $status === 'done');
            }

            echo json_encode(['success' => true, 'milestone' => fetchMilestone($pdo, $id)]);
            exit;
        }

        if ($action === 'complete') {
            $id          = (int)($data['id'] ?? 0);
            $postToFeed  = !empty($data['post_to_feed']);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'id required']);
                exit;
            }
            $m = fetchMilestone($pdo, $id);
            requireRoadmapMember($pdo, (int)$m['project_id'], $userId);

            $now = date('Y-m-d H:i:s');
            $pdo->prepare("UPDATE project_milestones SET status='done', completed_at=? WHERE id=?")
                ->execute([$now, $id]);

            $next = $pdo->prepare("
                SELECT id FROM project_milestones
                WHERE project_id=? AND position > ? AND status='pending'
                ORDER BY position ASC LIMIT 1
            ");
            $next->execute([(int)$m['project_id'], (int)$m['position']]);
            $nextId = $next->fetchColumn();
            if ($nextId) {
                $pdo->prepare("UPDATE project_milestones SET status='in_progress' WHERE id=?")
                    ->execute([(int)$nextId]);
            }

            syncProjectStage($pdo, (int)$m['project_id'], $m['stage_key'], true);

            $postId = null;
            if ($postToFeed) {
                $content = 'Веха «' . $m['title'] . '» достигнута!';
                $pdo->prepare("
                    INSERT INTO project_posts (project_id, author_id, type, title, content)
                    VALUES (?, ?, 'milestone', ?, ?)
                ")->execute([(int)$m['project_id'], $userId, $m['title'], $content]);
                $postId = (int)$pdo->lastInsertId();
            }

            echo json_encode(['success' => true, 'post_id' => $postId]);
            exit;
        }

        if ($action === 'link_task') {
            $milestoneId = (int)($data['milestone_id'] ?? 0);
            $taskId      = (int)($data['task_id'] ?? 0);
            if (!$milestoneId || !$taskId) {
                http_response_code(400);
                echo json_encode(['error' => 'milestone_id и task_id обязательны']);
                exit;
            }
            $m = fetchMilestone($pdo, $milestoneId);
            requireRoadmapMember($pdo, (int)$m['project_id'], $userId);

            $stmt = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task || (int)$task['project_id'] !== (int)$m['project_id']) {
                http_response_code(404);
                echo json_encode(['error' => 'Задача не найдена']);
                exit;
            }
            $pdo->prepare("UPDATE project_tasks SET milestone_id=? WHERE id=?")->execute([$milestoneId, $taskId]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'unlink_task') {
            $taskId = (int)($data['task_id'] ?? 0);
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['error' => 'task_id required']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT project_id FROM project_tasks WHERE id=?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) {
                http_response_code(404);
                echo json_encode(['error' => 'Задача не найдена']);
                exit;
            }
            requireRoadmapMember($pdo, (int)$task['project_id'], $userId);
            $pdo->prepare("UPDATE project_tasks SET milestone_id=NULL WHERE id=?")->execute([$taskId]);
            echo json_encode(['success' => true]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Неизвестный action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function ensureRoadmapSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_milestones (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        project_id   INT NOT NULL,
        stage_key    ENUM('idea','mvp','beta','launch') NOT NULL,
        title        VARCHAR(200) NOT NULL,
        description  TEXT,
        target_date  DATE DEFAULT NULL,
        status       ENUM('pending','in_progress','done') DEFAULT 'pending',
        position     INT DEFAULT 0,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_project_stage (project_id, stage_key),
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");

    $col = $pdo->query("SHOW COLUMNS FROM project_tasks LIKE 'milestone_id'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE project_tasks ADD COLUMN milestone_id INT DEFAULT NULL AFTER project_id");
    }

    $startCol = $pdo->query("SHOW COLUMNS FROM project_milestones LIKE 'start_date'")->fetch();
    if (!$startCol) {
        $pdo->exec('ALTER TABLE project_milestones ADD COLUMN start_date DATE DEFAULT NULL AFTER target_date');
    }
}

function seedDefaultMilestones(PDO $pdo, int $projectId): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_milestones WHERE project_id=?");
    $stmt->execute([$projectId]);
    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    $proj = $pdo->prepare("SELECT stage FROM projects WHERE id=?");
    $proj->execute([$projectId]);
    $projectStage = $proj->fetchColumn() ?: 'idea';
    $currentKey   = projectStageToRoadmapKey($projectStage);

    $foundCurrent = false;
    foreach (ROADMAP_STAGES as $s) {
        $status = 'pending';
        if ($s['stage_key'] === $currentKey) {
            $status        = 'in_progress';
            $foundCurrent  = true;
        } elseif (!$foundCurrent) {
            $status = 'done';
        }

        $pdo->prepare("
            INSERT INTO project_milestones (project_id, stage_key, title, position, status, completed_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $projectId,
            $s['stage_key'],
            $s['title'],
            $s['position'],
            $status,
            $status === 'done' ? date('Y-m-d H:i:s') : null,
        ]);
    }
}

function projectStageToRoadmapKey(string $stage): string
{
    $map = [
        'idea'       => 'idea',
        'prototype'  => 'idea',
        'mvp'        => 'mvp',
        'beta'       => 'beta',
        'launched'   => 'launch',
        'completed'  => 'launch',
    ];
    return $map[$stage] ?? 'idea';
}

function roadmapKeyToProjectStage(string $key, bool $completed): string
{
    if ($key === 'launch' && $completed) {
        return 'launched';
    }
    $map = ['idea' => 'idea', 'mvp' => 'mvp', 'beta' => 'beta', 'launch' => 'launched'];
    return $map[$key] ?? 'idea';
}

function syncProjectStage(PDO $pdo, int $projectId, string $stageKey, bool $completed): void
{
    $stage = roadmapKeyToProjectStage($stageKey, $completed);
    $pdo->prepare("UPDATE projects SET stage=? WHERE id=?")->execute([$stage, $projectId]);
}

function fetchMilestone(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare("SELECT * FROM project_milestones WHERE id=?");
    $stmt->execute([$id]);
    $m = $stmt->fetch();
    if (!$m) {
        http_response_code(404);
        echo json_encode(['error' => 'Веха не найдена']);
        exit;
    }
    return $m;
}

function requireRoadmapAccess(PDO $pdo, int $projectId, int $userId): void
{
    $stmt = $pdo->prepare("SELECT is_public FROM projects WHERE id=?");
    $stmt->execute([$projectId]);
    $p = $stmt->fetch();
    if (!$p) {
        http_response_code(404);
        echo json_encode(['error' => 'Проект не найден']);
        exit;
    }
    if (!$p['is_public']) {
        requireRoadmapMember($pdo, $projectId, $userId);
    }
}

function requireRoadmapMember(PDO $pdo, int $projectId, int $userId): void
{
    $stmt = $pdo->prepare("SELECT id FROM project_members WHERE project_id=? AND user_id=?");
    $stmt->execute([$projectId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Только участники команды']);
        exit;
    }
}
