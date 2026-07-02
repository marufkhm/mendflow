<?php
/**
 * project_calendar.php — aggregated calendar view (no duplicate tasks)
 * GET  ?project_id=&month=YYYY-MM
 * POST { action: create_meeting|update_meeting|delete_meeting, ... }
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

try {
    ensureProjectCalendarSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $userId    = verifyToken();
        $projectId = (int)($_GET['project_id'] ?? 0);
        $month     = trim($_GET['month'] ?? '');

        if (!$projectId) {
            http_response_code(400);
            echo json_encode(['error' => 'project_id required']);
            exit;
        }
        calendarRequireAccess($pdo, $projectId, $userId);

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $from = $month . '-01';
        $to   = date('Y-m-t', strtotime($from));

        $items = [];

        // ── Deadlines: tasks with due_date (reference only) ──
        $taskStmt = $pdo->prepare("
            SELECT t.id, t.title, t.status, t.priority, t.due_date, t.milestone_id,
                   u.first_name AS assignee_first, u.last_name AS assignee_last
            FROM project_tasks t
            LEFT JOIN users u ON u.id = t.assignee_id
            WHERE t.project_id = ? AND t.due_date IS NOT NULL
              AND t.due_date >= ? AND t.due_date <= ?
            ORDER BY t.due_date ASC, t.id ASC
        ");
        $taskStmt->execute([$projectId, $from, $to]);
        foreach ($taskStmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
            $assignee = trim(($t['assignee_first'] ?? '') . ' ' . ($t['assignee_last'] ?? ''));
            $dueDate = $t['due_date'] ? substr((string)$t['due_date'], 0, 10) : null;
            $items[] = [
                'type'        => 'deadline',
                'date'        => $dueDate,
                'title'       => $t['title'],
                'status'      => $t['status'],
                'priority'    => $t['priority'],
                'task_id'     => (int)$t['id'],
                'milestone_id'=> $t['milestone_id'] ? (int)$t['milestone_id'] : null,
                'assignee'    => $assignee ?: null,
            ];
        }

        // ── Stages: milestones with target_date / start_date ──
        $msStmt = $pdo->prepare("
            SELECT id, stage_key, title, status, target_date, start_date, description
            FROM project_milestones
            WHERE project_id = ?
              AND (
                (target_date IS NOT NULL AND target_date >= ? AND target_date <= ?)
                OR (start_date IS NOT NULL AND start_date >= ? AND start_date <= ?)
              )
            ORDER BY COALESCE(start_date, target_date) ASC
        ");
        $msStmt->execute([$projectId, $from, $to, $from, $to]);
        foreach ($msStmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $startDate = !empty($m['start_date']) ? substr((string)$m['start_date'], 0, 10) : null;
            $targetDate = !empty($m['target_date']) ? substr((string)$m['target_date'], 0, 10) : null;
            if ($startDate) {
                $items[] = [
                    'type'         => 'stage',
                    'date'         => $startDate,
                    'end_date'     => $targetDate ?: null,
                    'title'        => $m['title'],
                    'stage_key'    => $m['stage_key'],
                    'status'       => $m['status'],
                    'milestone_id' => (int)$m['id'],
                    'phase'        => 'start',
                ];
            }
            if ($targetDate) {
                $items[] = [
                    'type'         => 'stage',
                    'date'         => $targetDate,
                    'end_date'     => null,
                    'title'        => $m['title'],
                    'stage_key'    => $m['stage_key'],
                    'status'       => $m['status'],
                    'milestone_id' => (int)$m['id'],
                    'phase'        => $startDate ? 'end' : 'milestone',
                ];
            }
        }

        // ── Meetings ──
        $meetStmt = $pdo->prepare("
            SELECT m.id, m.title, m.description, m.starts_at, m.ends_at, m.meeting_link, m.created_by,
                   u.first_name AS creator_first, u.last_name AS creator_last
            FROM project_meetings m
            JOIN users u ON u.id = m.created_by
            WHERE m.project_id = ?
              AND DATE(m.starts_at) >= ? AND DATE(m.starts_at) <= ?
            ORDER BY m.starts_at ASC
        ");
        $meetStmt->execute([$projectId, $from, $to]);
        $meetings = $meetStmt->fetchAll(PDO::FETCH_ASSOC);
        $meetIds  = array_map(fn($r) => (int)$r['id'], $meetings);
        $participantsByMeeting = calendarFetchParticipants($pdo, $meetIds);

        foreach ($meetings as $m) {
            $mid = (int)$m['id'];
            $items[] = [
                'type'         => 'meeting',
                'date'         => substr($m['starts_at'], 0, 10),
                'starts_at'    => utcDate($m['starts_at']),
                'ends_at'      => $m['ends_at'] ? utcDate($m['ends_at']) : null,
                'title'        => $m['title'],
                'description'  => $m['description'],
                'meeting_link' => $m['meeting_link'],
                'meeting_id'   => $mid,
                'created_by'   => (int)$m['created_by'],
                'creator'      => trim(($m['creator_first'] ?? '') . ' ' . ($m['creator_last'] ?? '')),
                'participants' => $participantsByMeeting[$mid] ?? [],
            ];
        }

        usort($items, static function ($a, $b) {
            $da = ($a['starts_at'] ?? $a['date'] ?? '');
            $db = ($b['starts_at'] ?? $b['date'] ?? '');
            return strcmp($da, $db);
        });

        $byDate = [];
        foreach ($items as $item) {
            $d = $item['date'];
            if (!isset($byDate[$d])) {
                $byDate[$d] = [];
            }
            $byDate[$d][] = $item;
        }

        echo json_encode([
            'month'  => $month,
            'from'   => $from,
            'to'     => $to,
            'items'  => $items,
            'by_date'=> $byDate,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? '';

        if ($action === 'create_meeting') {
            $projectId = (int)($data['project_id'] ?? 0);
            $title     = trim($data['title'] ?? '');
            $startsAt  = trim($data['starts_at'] ?? '');
            if (!$projectId || !$title || !$startsAt) {
                http_response_code(400);
                echo json_encode(['error' => 'project_id, title и starts_at обязательны']);
                exit;
            }
            calendarRequireMember($pdo, $projectId, $userId);

            $endsAt = trim($data['ends_at'] ?? '');
            $pdo->prepare("
                INSERT INTO project_meetings (project_id, created_by, title, description, starts_at, ends_at, meeting_link)
                VALUES (?,?,?,?,?,?,?)
            ")->execute([
                $projectId,
                $userId,
                $title,
                trim($data['description'] ?? ''),
                calendarNormalizeDatetime($startsAt),
                $endsAt !== '' ? calendarNormalizeDatetime($endsAt) : null,
                trim($data['meeting_link'] ?? '') ?: null,
            ]);
            $meetingId = (int)$pdo->lastInsertId();
            calendarSyncParticipants($pdo, $meetingId, $data['participant_ids'] ?? [], $projectId);
            echo json_encode(['success' => true, 'meeting_id' => $meetingId]);
            exit;
        }

        if ($action === 'update_meeting') {
            $meetingId = (int)($data['id'] ?? 0);
            if (!$meetingId) {
                http_response_code(400);
                echo json_encode(['error' => 'id required']);
                exit;
            }
            $meeting = calendarFetchMeeting($pdo, $meetingId);
            calendarRequireMember($pdo, (int)$meeting['project_id'], $userId);

            $title = trim($data['title'] ?? $meeting['title']);
            $startsAt = array_key_exists('starts_at', $data)
                ? calendarNormalizeDatetime(trim((string)$data['starts_at']))
                : $meeting['starts_at'];
            $endsAt = array_key_exists('ends_at', $data)
                ? (trim((string)$data['ends_at']) !== '' ? calendarNormalizeDatetime(trim((string)$data['ends_at'])) : null)
                : $meeting['ends_at'];

            $pdo->prepare("
                UPDATE project_meetings
                SET title=?, description=?, starts_at=?, ends_at=?, meeting_link=?
                WHERE id=?
            ")->execute([
                $title,
                trim($data['description'] ?? $meeting['description'] ?? ''),
                $startsAt,
                $endsAt,
                array_key_exists('meeting_link', $data)
                    ? (trim((string)$data['meeting_link']) ?: null)
                    : $meeting['meeting_link'],
                $meetingId,
            ]);

            if (array_key_exists('participant_ids', $data)) {
                calendarSyncParticipants($pdo, $meetingId, $data['participant_ids'], (int)$meeting['project_id']);
            }
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'delete_meeting') {
            $meetingId = (int)($data['id'] ?? 0);
            if (!$meetingId) {
                http_response_code(400);
                echo json_encode(['error' => 'id required']);
                exit;
            }
            $meeting = calendarFetchMeeting($pdo, $meetingId);
            calendarRequireMember($pdo, (int)$meeting['project_id'], $userId);
            $pdo->prepare('DELETE FROM project_meetings WHERE id=?')->execute([$meetingId]);
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

function ensureProjectCalendarSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_meetings (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        project_id   INT NOT NULL,
        created_by   INT NOT NULL,
        title        VARCHAR(200) NOT NULL,
        description  TEXT,
        starts_at    DATETIME NOT NULL,
        ends_at      DATETIME DEFAULT NULL,
        meeting_link VARCHAR(500) DEFAULT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_pmeet_project (project_id),
        INDEX idx_pmeet_starts (starts_at),
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_meeting_participants (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id INT NOT NULL,
        user_id    INT NOT NULL,
        UNIQUE KEY uq_meeting_user (meeting_id, user_id),
        FOREIGN KEY (meeting_id) REFERENCES project_meetings(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    try {
        $col = $pdo->query("SHOW COLUMNS FROM project_milestones LIKE 'start_date'")->fetch();
        if (!$col) {
            $pdo->exec('ALTER TABLE project_milestones ADD COLUMN start_date DATE DEFAULT NULL AFTER target_date');
        }
    } catch (Throwable $e) {
    }
}

function calendarRequireAccess(PDO $pdo, int $projectId, int $userId): void
{
    $stmt = $pdo->prepare('SELECT is_public FROM projects WHERE id=?');
    $stmt->execute([$projectId]);
    $p = $stmt->fetch();
    if (!$p) {
        http_response_code(404);
        echo json_encode(['error' => 'Проект не найден']);
        exit;
    }
    if (!$p['is_public']) {
        calendarRequireMember($pdo, $projectId, $userId);
    }
}

function calendarRequireMember(PDO $pdo, int $projectId, int $userId): void
{
    $stmt = $pdo->prepare('SELECT id FROM project_members WHERE project_id=? AND user_id=?');
    $stmt->execute([$projectId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Только участники команды']);
        exit;
    }
}

function calendarFetchMeeting(PDO $pdo, int $meetingId): array
{
    $stmt = $pdo->prepare('SELECT * FROM project_meetings WHERE id=?');
    $stmt->execute([$meetingId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Встреча не найдена']);
        exit;
    }
    return $row;
}

function calendarNormalizeDatetime(string $value): string
{
    $value = str_replace('T', ' ', trim($value));
    if (strlen($value) === 10) {
        return $value . ' 12:00:00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
        return $value . ':00';
    }
    return $value;
}

/** @return array<int, array<int, array>> */
function calendarFetchParticipants(PDO $pdo, array $meetingIds): array
{
    $map = [];
    if (!$meetingIds) {
        return $map;
    }
    $ph = implode(',', array_fill(0, count($meetingIds), '?'));
    $stmt = $pdo->prepare("
        SELECT mp.meeting_id, u.id, u.first_name, u.last_name, u.avatar
        FROM project_meeting_participants mp
        JOIN users u ON u.id = mp.user_id
        WHERE mp.meeting_id IN ($ph)
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->execute($meetingIds);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $mid = (int)$row['meeting_id'];
        $map[$mid][] = [
            'id'         => (int)$row['id'],
            'first_name' => $row['first_name'],
            'last_name'  => $row['last_name'],
            'avatar'     => $row['avatar'],
        ];
    }
    return $map;
}

function calendarSyncParticipants(PDO $pdo, int $meetingId, array $participantIds, int $projectId): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $participantIds))));
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT user_id FROM project_members
            WHERE project_id = ? AND user_id IN ($ph)
        ");
        $stmt->execute(array_merge([$projectId], $ids));
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    $pdo->prepare('DELETE FROM project_meeting_participants WHERE meeting_id=?')->execute([$meetingId]);
    if (!$ids) {
        return;
    }
    $ins = $pdo->prepare('INSERT INTO project_meeting_participants (meeting_id, user_id) VALUES (?,?)');
    foreach ($ids as $uid) {
        $ins->execute([$meetingId, $uid]);
    }
}
