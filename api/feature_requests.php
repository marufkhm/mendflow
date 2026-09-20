<?php
/**
 * feature_requests.php — Идеи и улучшения (platform + project)
 * GET  ?platform=1 | ?project_id=N | ?id=N
 * POST { action: create|vote|update_status|delete, ... }
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

const FR_STATUSES = ['proposed', 'reviewing', 'in_progress', 'done', 'declined'];

try {
    ensureFeatureRequestsSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $userId    = verifyTokenSoft();
        $id        = (int)($_GET['id'] ?? 0);
        $projectId = (int)($_GET['project_id'] ?? 0);
        $platform  = !empty($_GET['platform']);

        if ($id) {
            $idea = fetchFeatureRequest($pdo, $id, $userId);
            echo json_encode(['idea' => $idea], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($projectId) {
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Требуется авторизация']);
                exit;
            }
            frRequireMember($pdo, $projectId, $userId);
            $ideas = listFeatureRequests($pdo, $projectId, $userId);
            echo json_encode(['ideas' => $ideas], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($platform) {
            $ideas = listFeatureRequests($pdo, null, $userId);
            echo json_encode(['ideas' => $ideas], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'platform=1, project_id или id required']);
        exit;
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? '';

        if ($action === 'create') {
            $title = trim($data['title'] ?? '');
            $desc  = trim($data['description'] ?? '');
            $projectId = isset($data['project_id']) && $data['project_id'] !== null && $data['project_id'] !== ''
                ? (int)$data['project_id']
                : null;

            if (!$title) {
                http_response_code(400);
                echo json_encode(['error' => 'title обязателен']);
                exit;
            }

            if ($projectId) {
                frRequireMember($pdo, $projectId, $userId);
            }

            $pdo->prepare("
                INSERT INTO feature_requests (title, description, project_id, author_id, status, votes)
                VALUES (?,?,?,?,'proposed',0)
            ")->execute([$title, $desc, $projectId, $userId]);

            $newId = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'idea' => fetchFeatureRequest($pdo, $newId, $userId)]);
            exit;
        }

        if ($action === 'vote') {
            $ideaId = (int)($data['id'] ?? 0);
            if (!$ideaId) {
                http_response_code(400);
                echo json_encode(['error' => 'id required']);
                exit;
            }
            $idea = fetchFeatureRequestRow($pdo, $ideaId);
            frRequireVoteAccess($pdo, $idea, $userId);

            $voted = frToggleVote($pdo, $ideaId, $userId);
            echo json_encode([
                'success'    => true,
                'voted'      => $voted,
                'votes'      => frCountVotes($pdo, $ideaId),
                'idea'       => fetchFeatureRequest($pdo, $ideaId, $userId),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'update_status') {
            $ideaId = (int)($data['id'] ?? 0);
            $status = trim($data['status'] ?? '');
            if (!$ideaId || !in_array($status, FR_STATUSES, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'id и корректный status обязательны']);
                exit;
            }

            $idea = fetchFeatureRequestRow($pdo, $ideaId);
            frRequireModerator($pdo, $idea, $userId);

            $declineReason = trim($data['decline_reason'] ?? '');
            if ($status === 'declined' && $declineReason === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Укажите причину отклонения']);
                exit;
            }
            if ($status !== 'declined') {
                $declineReason = null;
            }

            $oldStatus = $idea['status'];
            if ($oldStatus === $status && ($status !== 'declined' || ($idea['decline_reason'] ?? '') === $declineReason)) {
                echo json_encode(['success' => true, 'idea' => fetchFeatureRequest($pdo, $ideaId, $userId)]);
                exit;
            }

            $pdo->prepare("
                UPDATE feature_requests
                SET status=?, decline_reason=?, updated_at=NOW()
                WHERE id=?
            ")->execute([$status, $declineReason, $ideaId]);

            frNotifyStatusChange($pdo, $ideaId, $oldStatus, $status, $userId, $declineReason);

            echo json_encode(['success' => true, 'idea' => fetchFeatureRequest($pdo, $ideaId, $userId)]);
            exit;
        }

        if ($action === 'delete') {
            $ideaId = (int)($data['id'] ?? 0);
            if (!$ideaId) {
                http_response_code(400);
                echo json_encode(['error' => 'id required']);
                exit;
            }
            $idea = fetchFeatureRequestRow($pdo, $ideaId);
            $isAuthor = (int)$idea['author_id'] === $userId;
            $canMod   = frCanModerate($pdo, $idea, $userId);
            if (!$isAuthor && !$canMod) {
                http_response_code(403);
                echo json_encode(['error' => 'Нет прав']);
                exit;
            }
            $pdo->prepare('DELETE FROM feature_requests WHERE id=?')->execute([$ideaId]);
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

function ensureFeatureRequestsSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS feature_requests (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        title          VARCHAR(200) NOT NULL,
        description    TEXT,
        project_id     INT DEFAULT NULL,
        author_id      INT NOT NULL,
        status         ENUM('proposed','reviewing','in_progress','done','declined') DEFAULT 'proposed',
        votes          INT NOT NULL DEFAULT 0,
        decline_reason TEXT,
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_fr_project (project_id),
        KEY idx_fr_author (author_id),
        KEY idx_fr_votes (votes),
        KEY idx_fr_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS feature_request_votes (
        id                 INT AUTO_INCREMENT PRIMARY KEY,
        feature_request_id INT NOT NULL,
        user_id            INT NOT NULL,
        created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_fr_vote (feature_request_id, user_id),
        KEY idx_fr_votes_user (user_id),
        KEY idx_fr_votes_idea (feature_request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try {
        $col = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'feature_request_id'")->fetch();
        if (!$col) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN feature_request_id INT DEFAULT NULL AFTER post_id');
        }
    } catch (Throwable $e) {
    }

    $done = true;
}

function frRequireMember(PDO $pdo, int $projectId, int $userId): void
{
    $stmt = $pdo->prepare('SELECT id FROM project_members WHERE project_id=? AND user_id=?');
    $stmt->execute([$projectId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Только участники команды']);
        exit;
    }
}

function frRequireVoteAccess(PDO $pdo, array $idea, int $userId): void
{
    if (!empty($idea['project_id'])) {
        frRequireMember($pdo, (int)$idea['project_id'], $userId);
    }
}

function frCanModerate(PDO $pdo, array $idea, int $userId): bool
{
    if (empty($idea['project_id'])) {
        return canModeratePosts($userId);
    }
    $stmt = $pdo->prepare("
        SELECT permission_role FROM project_members
        WHERE project_id=? AND user_id=? AND permission_role IN ('owner','admin')
    ");
    $stmt->execute([(int)$idea['project_id'], $userId]);
    return (bool)$stmt->fetch();
}

function frRequireModerator(PDO $pdo, array $idea, int $userId): void
{
    if (!frCanModerate($pdo, $idea, $userId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Нет прав на смену статуса']);
        exit;
    }
}

function fetchFeatureRequestRow(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM feature_requests WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Идея не найдена']);
        exit;
    }
    return $row;
}

function frCountVotes(PDO $pdo, int $ideaId): int
{
    $stmt = $pdo->prepare('SELECT votes FROM feature_requests WHERE id=?');
    $stmt->execute([$ideaId]);
    return (int)$stmt->fetchColumn();
}

function frToggleVote(PDO $pdo, int $ideaId, int $userId): bool
{
    $check = $pdo->prepare('SELECT id FROM feature_request_votes WHERE feature_request_id=? AND user_id=?');
    $check->execute([$ideaId, $userId]);
    $existing = $check->fetch();

    if ($existing) {
        $pdo->prepare('DELETE FROM feature_request_votes WHERE feature_request_id=? AND user_id=?')
            ->execute([$ideaId, $userId]);
        $pdo->prepare('UPDATE feature_requests SET votes = GREATEST(votes - 1, 0) WHERE id=?')
            ->execute([$ideaId]);
        return false;
    }

    $pdo->prepare('INSERT INTO feature_request_votes (feature_request_id, user_id) VALUES (?,?)')
        ->execute([$ideaId, $userId]);
    $pdo->prepare('UPDATE feature_requests SET votes = votes + 1 WHERE id=?')
        ->execute([$ideaId]);
    return true;
}

function listFeatureRequests(PDO $pdo, ?int $projectId, ?int $userId): array
{
    $uid = $userId ? (int)$userId : 0;
    if ($projectId) {
        $stmt = $pdo->prepare("
            SELECT fr.*,
                   u.first_name, u.last_name, u.avatar,
                   EXISTS(
                       SELECT 1 FROM feature_request_votes v
                       WHERE v.feature_request_id = fr.id AND v.user_id = ?
                   ) AS i_voted,
                   (? IN (
                       SELECT pm.user_id FROM project_members pm
                       WHERE pm.project_id = fr.project_id AND pm.permission_role IN ('owner','admin')
                   )) AS can_moderate
            FROM feature_requests fr
            JOIN users u ON u.id = fr.author_id
            WHERE fr.project_id = ?
            ORDER BY fr.votes DESC, fr.created_at DESC
        ");
        $stmt->execute([$uid, $uid, $projectId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT fr.*,
                   u.first_name, u.last_name, u.avatar,
                   EXISTS(
                       SELECT 1 FROM feature_request_votes v
                       WHERE v.feature_request_id = fr.id AND v.user_id = ?
                   ) AS i_voted,
                   0 AS can_moderate
            FROM feature_requests fr
            JOIN users u ON u.id = fr.author_id
            WHERE fr.project_id IS NULL
            ORDER BY fr.votes DESC, fr.created_at DESC
        ");
        $stmt->execute([$uid]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r = frShapeIdea($r, $userId);
        if (!$projectId && $userId && canModeratePosts($userId)) {
            $r['can_moderate'] = true;
        }
    }
    unset($r);
    return $rows;
}

function fetchFeatureRequest(PDO $pdo, int $id, ?int $userId): array
{
    $uid = $userId ? (int)$userId : 0;
    $stmt = $pdo->prepare("
        SELECT fr.*,
               u.first_name, u.last_name, u.avatar,
               EXISTS(
                   SELECT 1 FROM feature_request_votes v
                   WHERE v.feature_request_id = fr.id AND v.user_id = ?
               ) AS i_voted,
               0 AS can_moderate
        FROM feature_requests fr
        JOIN users u ON u.id = fr.author_id
        WHERE fr.id = ?
    ");
    $stmt->execute([$uid, $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Идея не найдена']);
        exit;
    }

    if (!empty($row['project_id']) && $userId) {
        $m = $pdo->prepare("
            SELECT permission_role FROM project_members
            WHERE project_id=? AND user_id=? AND permission_role IN ('owner','admin')
        ");
        $m->execute([(int)$row['project_id'], $userId]);
        $row['can_moderate'] = (bool)$m->fetch();
    } elseif (!$row['project_id'] && $userId && canModeratePosts($userId)) {
        $row['can_moderate'] = true;
    }

    if (!empty($row['project_id']) && $userId) {
        frRequireMember($pdo, (int)$row['project_id'], $userId);
    }

    return frShapeIdea($row, $userId);
}

function frShapeIdea(array $r, ?int $userId): array
{
    $isAuthor = $userId && (int)$r['author_id'] === (int)$userId;
    return [
        'id'             => (int)$r['id'],
        'title'          => $r['title'],
        'description'    => $r['description'] ?? '',
        'project_id'     => $r['project_id'] !== null ? (int)$r['project_id'] : null,
        'author_id'      => (int)$r['author_id'],
        'author'         => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
        'avatar'         => $r['avatar'] ?? null,
        'status'         => $r['status'],
        'votes'          => (int)$r['votes'],
        'decline_reason' => $r['decline_reason'] ?? null,
        'i_voted'        => !empty($r['i_voted']),
        'can_moderate'   => !empty($r['can_moderate']),
        'is_author'      => $isAuthor,
        'created_at'     => utcDate($r['created_at']),
        'time_ago'       => timeAgo($r['created_at']),
    ];
}

function frStatusLabel(string $status): string
{
    return [
        'proposed'    => 'Предложено',
        'reviewing'   => 'На рассмотрении',
        'in_progress' => 'В работе',
        'done'        => 'Готово',
        'declined'    => 'Отклонено',
    ][$status] ?? $status;
}

function frNotifyStatusChange(PDO $pdo, int $ideaId, string $oldStatus, string $newStatus, int $actorId, ?string $declineReason): void
{
    try {
        $tableCheck = $pdo->query("
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' LIMIT 1
        ")->fetchColumn();
        if (!$tableCheck) {
            return;
        }

        $idea = fetchFeatureRequestRow($pdo, $ideaId);
        $label = frStatusLabel($newStatus);
        $preview = '«' . mb_substr($idea['title'], 0, 80) . '» → ' . $label;
        if ($newStatus === 'declined' && $declineReason) {
            $preview .= ': ' . mb_substr($declineReason, 0, 60);
        }
        $postType = $idea['project_id'] ? ('project_' . (int)$idea['project_id']) : 'platform';

        $recipients = [];

        $authorId = (int)$idea['author_id'];
        if ($authorId !== $actorId) {
            $recipients[$authorId] = true;
        }

        $voters = $pdo->prepare('SELECT user_id FROM feature_request_votes WHERE feature_request_id=?');
        $voters->execute([$ideaId]);
        foreach ($voters->fetchAll(PDO::FETCH_COLUMN) as $vid) {
            $vid = (int)$vid;
            if ($vid !== $actorId) {
                $recipients[$vid] = true;
            }
        }

        $hasFrCol = (bool)$pdo->query("SHOW COLUMNS FROM notifications LIKE 'feature_request_id'")->fetch();
        if ($hasFrCol) {
            $ins = $pdo->prepare("
                INSERT INTO notifications (user_id, from_user_id, type, post_id, feature_request_id, post_preview, post_type, created_at)
                VALUES (?, ?, 'feature_status', ?, ?, ?, ?, NOW())
            ");
            foreach (array_keys($recipients) as $uid) {
                $ins->execute([$uid, $actorId, $ideaId, $ideaId, $preview, $postType]);
            }
        } else {
            $ins = $pdo->prepare("
                INSERT INTO notifications (user_id, from_user_id, type, post_id, post_preview, post_type, created_at)
                VALUES (?, ?, 'feature_status', ?, ?, ?, NOW())
            ");
            foreach (array_keys($recipients) as $uid) {
                $ins->execute([$uid, $actorId, $ideaId, $preview, $postType]);
            }
        }
    } catch (Throwable $e) {
    }
}
