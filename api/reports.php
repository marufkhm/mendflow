<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Moderator-Password');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

require_once 'db.php';
require_once 'moderation_lib.php';

function reportsJson($data, $code = 200): void
{
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    global $pdo;
    ensureReportsSchema();
    ensureModerationLogSchema();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $reporterId = verifyToken();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $targetType = trim($data['target_type'] ?? '');
        $targetId   = (int)($data['target_id'] ?? 0);
        $reason     = trim($data['reason'] ?? '');
        $comment    = trim($data['comment'] ?? '');

        if (!$targetType || !$targetId) {
            reportsJson(['error' => 'target_type and target_id required'], 400);
        }
        if (!in_array($targetType, MF_REPORT_TARGETS, true)) {
            reportsJson(['error' => 'Invalid target_type'], 400);
        }
        if (!in_array($reason, MF_REPORT_REASONS, true)) {
            reportsJson(['error' => 'Invalid reason'], 400);
        }
        if (mb_strlen($comment) > 2000) {
            reportsJson(['error' => 'Comment too long'], 400);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO reports (reporter_id, target_type, target_id, reason, comment, status, created_at)
             VALUES (?, ?, ?, ?, ?, "new", UTC_TIMESTAMP())'
        );
        $stmt->execute([
            $reporterId,
            $targetType,
            $targetId,
            $reason,
            $comment !== '' ? $comment : null,
        ]);

        $reportId = (int)$pdo->lastInsertId();

        try {
            mfNotifyModeratorsNewReport($pdo, $reportId, $targetType, $targetId, $reason, $comment ?: null, $reporterId);
        } catch (Throwable $e) {}

        reportsJson([
            'success' => true,
            'id'      => $reportId,
            'message' => 'Report submitted',
        ]);
    }

    if ($method === 'GET') {
        $adminId = verifyToken();
        if (!canPlatformAdmin($adminId)) {
            reportsJson(['error' => 'Forbidden'], 403);
        }
        mfRequireModeratorPanelPassword();

        $status = trim($_GET['status'] ?? 'new');
        if (!in_array($status, ['new', 'reviewed', 'resolved', 'all'], true)) {
            $status = 'new';
        }
        $limit  = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $where = $status === 'all' ? '1=1' : 'r.status = ?';
        $params = $status === 'all' ? [] : [$status];
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare("
            SELECT
                r.id, r.reporter_id, r.target_type, r.target_id,
                r.reason, r.comment, r.status, r.created_at,
                u.first_name AS reporter_first_name,
                u.last_name AS reporter_last_name,
                u.email AS reporter_email
            FROM reports r
            JOIN users u ON u.id = r.reporter_id
            WHERE {$where}
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['id']          = (int)$row['id'];
            $row['reporter_id'] = (int)$row['reporter_id'];
            $row['target_id']   = (int)$row['target_id'];
            $row['created_at']  = utcDate($row['created_at'] ?? null);
            $row['target_info'] = mfReportTargetInfo($pdo, $row['target_type'], $row['target_id']);
        }
        unset($row);

        reportsJson(['reports' => $rows, 'status' => $status]);
    }

    if ($method === 'PATCH') {
        $adminId = verifyToken();
        if (!canPlatformAdmin($adminId)) {
            reportsJson(['error' => 'Forbidden'], 403);
        }
        mfRequireModeratorPanelPassword();

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $id             = (int)($data['id'] ?? 0);
        $status         = trim($data['status'] ?? '');
        $deleteContent  = !empty($data['delete_content']);
        $banAuthor      = !empty($data['ban_author']);
        $banReason      = trim($data['ban_reason'] ?? '');

        if (!$id) {
            reportsJson(['error' => 'id required'], 400);
        }

        $stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$report) {
            reportsJson(['error' => 'Report not found'], 404);
        }

        $result = ['success' => true];

        if ($deleteContent) {
            $del = mfModerationDeleteContent($pdo, $report['target_type'], (int)$report['target_id']);
            if (!$del['ok']) {
                reportsJson(['error' => $del['error'] ?? 'Delete failed'], 400);
            }
            mfModerationLog($pdo, $adminId, 'delete_content', $report['target_type'], (int)$report['target_id'], 'report #' . $id);
            $result['content_deleted'] = true;
            if (!$status) {
                $status = 'resolved';
            }
        }

        if ($banAuthor) {
            $info = mfReportTargetInfo($pdo, $report['target_type'], (int)$report['target_id']);
            $authorId = (int)($info['author_id'] ?? 0);
            if ($report['target_type'] === 'user') {
                $authorId = (int)$report['target_id'];
            }
            if ($authorId > 0) {
                ensurePlatformBanSchema();
                mfPlatformBanUser($pdo, $adminId, $authorId, $banReason ?: 'Report #' . $id);
                $result['author_banned'] = true;
            }
            if (!$status) {
                $status = 'resolved';
            }
        }

        if ($status && in_array($status, ['new', 'reviewed', 'resolved'], true)) {
            $upd = $pdo->prepare('UPDATE reports SET status = ? WHERE id = ?');
            $upd->execute([$status, $id]);
            $result['status'] = $status;
        }

        reportsJson($result);
    }

    reportsJson(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    reportsJson(['error' => 'Server error'], 500);
}
