<?php
/**
 * project_resource_schedule.php — расписание команды (Float-style weekly grid)
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

function ensureResourceScheduleSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_resource_allocations (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        project_id  INT NOT NULL,
        user_id     INT NOT NULL,
        week_start  DATE NOT NULL,
        hours       DECIMAL(6,2) NOT NULL DEFAULT 0,
        label       VARCHAR(120) NOT NULL DEFAULT '',
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_alloc (project_id, user_id, week_start),
        KEY idx_alloc_project_week (project_id, week_start)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $col = $pdo->query("SHOW COLUMNS FROM project_members LIKE 'weekly_capacity'")->fetch();
    if (!$col) {
        try {
            $pdo->exec('ALTER TABLE project_members ADD COLUMN weekly_capacity DECIMAL(5,2) NOT NULL DEFAULT 40 AFTER specialization_role');
        } catch (Throwable $e) {}
    }
}

function scheduleRequireAccess(PDO $pdo, int $projectId, ?int $userId): void
{
    $stmt = $pdo->prepare('SELECT is_public FROM projects WHERE id=?');
    $stmt->execute([$projectId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
        mfJsonResponse(['error' => 'Проект не найден'], 404);
    }
    if (!$p['is_public']) {
        if (!$userId) {
            mfJsonResponse(['error' => 'Нет доступа'], 403);
        }
        $m = $pdo->prepare('SELECT id FROM project_members WHERE project_id=? AND user_id=?');
        $m->execute([$projectId, $userId]);
        if (!$m->fetch()) {
            mfJsonResponse(['error' => 'Нет доступа'], 403);
        }
    }
}

function scheduleCanEdit(PDO $pdo, int $projectId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT permission_role FROM project_members WHERE project_id=? AND user_id=?');
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && in_array($row['permission_role'], ['owner', 'admin'], true);
}

function scheduleRequireEdit(PDO $pdo, int $projectId, int $userId): void
{
    if (!scheduleCanEdit($pdo, $projectId, $userId)) {
        mfJsonResponse(['error' => 'Недостаточно прав'], 403);
    }
}

function scheduleMonday(string $date): string
{
    $ts = strtotime($date);
    if ($ts === false) {
        $ts = time();
    }
    $dow = (int)date('N', $ts);
    if ($dow > 1) {
        $ts -= ($dow - 1) * 86400;
    }
    return date('Y-m-d', $ts);
}

function scheduleWeekList(string $startMonday, int $weeks): array
{
    $weeks = max(1, min(12, $weeks));
    $list  = [];
    $ts    = strtotime($startMonday);
    for ($i = 0; $i < $weeks; $i++) {
        $list[] = date('Y-m-d', $ts + $i * 7 * 86400);
    }
    return $list;
}

function fetchResourceSchedule(PDO $pdo, int $projectId, string $startMonday, int $weeks): array
{
    ensureResourceScheduleSchema($pdo);
    $weekList = scheduleWeekList($startMonday, $weeks);
    $endMonday = $weekList[count($weekList) - 1];

    $stmt = $pdo->prepare("
        SELECT pm.user_id, pm.permission_role, pm.specialization_role,
               COALESCE(pm.weekly_capacity, 40) AS weekly_capacity,
               u.first_name, u.last_name, u.avatar
        FROM project_members pm
        JOIN users u ON u.id = pm.user_id
        WHERE pm.project_id = ?
        ORDER BY FIELD(pm.permission_role,'owner','admin','member','viewer'), pm.joined_at ASC
    ");
    $stmt->execute([$projectId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($members as &$m) {
        $m['user_id']         = (int)$m['user_id'];
        $m['weekly_capacity'] = (float)$m['weekly_capacity'];
    }
    unset($m);

    $stmt = $pdo->prepare('
        SELECT id, user_id, week_start, hours, label
        FROM project_resource_allocations
        WHERE project_id = ? AND week_start >= ? AND week_start <= ?
        ORDER BY week_start ASC, user_id ASC
    ');
    $stmt->execute([$projectId, $weekList[0], $endMonday]);
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allocations as &$a) {
        $a['id']      = (int)$a['id'];
        $a['user_id'] = (int)$a['user_id'];
        $a['hours']   = (float)$a['hours'];
    }
    unset($a);

    return [
        'weeks'       => $weekList,
        'members'     => $members,
        'allocations' => $allocations,
    ];
}

if (mfIsDirectScript(__FILE__)) {
    try {
        ensureResourceScheduleSchema($pdo);
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $viewerId  = verifyTokenSoft();
            $projectId = (int)($_GET['project_id'] ?? 0);
            if (!$projectId) {
                mfJsonResponse(['error' => 'project_id required'], 400);
            }
            scheduleRequireAccess($pdo, $projectId, $viewerId);

            $start = scheduleMonday($_GET['start'] ?? date('Y-m-d'));
            $weeks = (int)($_GET['weeks'] ?? 6);
            $data  = fetchResourceSchedule($pdo, $projectId, $start, $weeks);
            $data['can_edit'] = $viewerId ? scheduleCanEdit($pdo, $projectId, $viewerId) : false;
            $data['start']    = $start;

            mfJsonResponse($data);
        }

        if ($method === 'POST') {
            $userId = verifyToken();
            $data   = json_decode(file_get_contents('php://input'), true) ?? [];
            $action = $data['action'] ?? '';

            if ($action === 'set_capacity') {
                $projectId = (int)($data['project_id'] ?? 0);
                $targetId  = (int)($data['user_id'] ?? 0);
                $capacity  = max(0, min(168, (float)($data['weekly_capacity'] ?? 40)));
                if (!$projectId || !$targetId) {
                    mfJsonResponse(['error' => 'project_id и user_id обязательны'], 400);
                }
                scheduleRequireEdit($pdo, $projectId, $userId);
                $pdo->prepare('UPDATE project_members SET weekly_capacity=? WHERE project_id=? AND user_id=?')
                    ->execute([$capacity, $projectId, $targetId]);
                mfJsonResponse(['success' => true, 'weekly_capacity' => $capacity]);
            }

            if ($action === 'upsert_allocation') {
                $projectId = (int)($data['project_id'] ?? 0);
                $targetId  = (int)($data['user_id'] ?? 0);
                $weekStart = scheduleMonday($data['week_start'] ?? '');
                $hours     = max(0, min(168, (float)($data['hours'] ?? 0)));
                $label     = mb_substr(trim($data['label'] ?? ''), 0, 120);
                if (!$projectId || !$targetId || !$weekStart) {
                    mfJsonResponse(['error' => 'project_id, user_id и week_start обязательны'], 400);
                }
                scheduleRequireEdit($pdo, $projectId, $userId);

                $chk = $pdo->prepare('SELECT id FROM project_members WHERE project_id=? AND user_id=?');
                $chk->execute([$projectId, $targetId]);
                if (!$chk->fetch()) {
                    mfJsonResponse(['error' => 'Участник не в проекте'], 400);
                }

                if ($hours <= 0 && $label === '') {
                    $pdo->prepare('DELETE FROM project_resource_allocations WHERE project_id=? AND user_id=? AND week_start=?')
                        ->execute([$projectId, $targetId, $weekStart]);
                    mfJsonResponse(['success' => true, 'deleted' => true]);
                }

                $pdo->prepare('
                    INSERT INTO project_resource_allocations (project_id, user_id, week_start, hours, label)
                    VALUES (?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE hours=VALUES(hours), label=VALUES(label)
                ')->execute([$projectId, $targetId, $weekStart, $hours, $label]);

                $stmt = $pdo->prepare('
                    SELECT id, user_id, week_start, hours, label
                    FROM project_resource_allocations
                    WHERE project_id=? AND user_id=? AND week_start=?
                ');
                $stmt->execute([$projectId, $targetId, $weekStart]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $row['id']      = (int)$row['id'];
                    $row['user_id'] = (int)$row['user_id'];
                    $row['hours']   = (float)$row['hours'];
                }
                mfJsonResponse(['success' => true, 'allocation' => $row]);
            }

            if ($action === 'delete_allocation') {
                $id = (int)($data['id'] ?? 0);
                $stmt = $pdo->prepare('SELECT project_id FROM project_resource_allocations WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    mfJsonResponse(['error' => 'Не найдено'], 404);
                }
                scheduleRequireEdit($pdo, (int)$row['project_id'], $userId);
                $pdo->prepare('DELETE FROM project_resource_allocations WHERE id=?')->execute([$id]);
                mfJsonResponse(['success' => true]);
            }

            mfJsonResponse(['error' => 'Неизвестный action'], 400);
        }

        mfJsonResponse(['error' => 'Метод не разрешён'], 405);
    } catch (PDOException $e) {
        mfJsonResponse(['error' => 'DB: ' . $e->getMessage()], 500);
    } catch (Throwable $e) {
        mfJsonResponse(['error' => $e->getMessage()], 500);
    }
}
