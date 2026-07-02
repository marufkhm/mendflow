<?php
/**
 * Presence API
 * Sprint 5: Realtime Presence System
 *
 * POST /api/presence.php  { project_id, action, context? }
 *   action: "heartbeat" | "editing" | "idle"
 *
 * GET  /api/presence.php?project_id=123
 *   Returns online members for a project
 *
 * GET  /api/presence.php?user_ids=1,2,3
 *   Returns online status for specific users
 */

declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int)$authUser['id'];
$method = $_SERVER['REQUEST_METHOD'];

/* ── POST — update presence ──────────────────────────────── */
if ($method === 'POST') {
    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $projectId = (int)($body['project_id'] ?? 0);
    $action    = in_array($body['action'] ?? '', ['heartbeat','editing','idle'], true)
                 ? $body['action'] : 'heartbeat';
    $context   = isset($body['context']) ? substr((string)$body['context'], 0, 120) : null;

    $status = ($action === 'idle') ? 'idle' : 'connected';

    try {
        $pdo->prepare(
            'INSERT INTO realtime_presence
               (user_id, project_id, status, editing_context, last_seen)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
               status           = VALUES(status),
               editing_context  = VALUES(editing_context),
               last_seen        = NOW()'
        )->execute([$userId, $projectId ?: null, $status, $context]);

        echo json_encode(['ok' => true]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* ── GET — query presence ────────────────────────────────── */
if (isset($_GET['user_ids'])) {
    // /api/presence.php?user_ids=1,2,3
    $rawIds = explode(',', $_GET['user_ids']);
    $ids    = array_filter(array_map('intval', $rawIds));
    if (empty($ids)) {
        echo json_encode(['online' => (object)[]]);
        exit;
    }

    $in   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT rp.user_id,
                rp.status,
                rp.last_seen,
                CASE WHEN rp.last_seen >= DATE_SUB(NOW(), INTERVAL 35 SECOND) THEN 1 ELSE 0 END AS is_online
         FROM realtime_presence rp
         WHERE rp.user_id IN ({$in})
         GROUP BY rp.user_id
         ORDER BY rp.last_seen DESC"
    );
    $stmt->execute($ids);
    $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($rows as $row) {
        $result[$row['user_id']] = [
            'is_online' => (bool)$row['is_online'],
            'status'    => $row['status'],
            'last_seen' => $row['last_seen'],
        ];
    }
    echo json_encode(['online' => $result]);
    exit;
}

if (isset($_GET['project_id'])) {
    // /api/presence.php?project_id=123
    $projectId = (int)$_GET['project_id'];

    $stmt = $pdo->prepare(
        'SELECT rp.user_id,
                rp.status,
                rp.editing_context,
                rp.last_seen,
                u.first_name,
                u.last_name,
                u.avatar
         FROM realtime_presence rp
         JOIN users u ON u.id = rp.user_id
         WHERE rp.project_id = ?
           AND rp.last_seen >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
         ORDER BY rp.last_seen DESC
         LIMIT 30'
    );
    $stmt->execute([$projectId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['members' => $members, 'count' => count($members)]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'project_id or user_ids required']);
