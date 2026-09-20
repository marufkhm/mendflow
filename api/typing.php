<?php
/**
 * Typing Indicators API
 * Sprint 5: Realtime Collaboration
 *
 * POST /api/typing.php
 *   Body: { context_type, context_id, is_typing }
 *   context_type: "project_post_comment" | "chat" | "project_doc"
 *
 * GET  /api/typing.php?context_type=project_post_comment&context_id=42
 *   Returns users currently typing in this context
 */

declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int)$authUser['id'];
$method = $_SERVER['REQUEST_METHOD'];

/* ── POST — set typing state ─────────────────────────────── */
if ($method === 'POST') {
    $body        = json_decode(file_get_contents('php://input'), true) ?? [];
    $ctxType     = in_array($body['context_type'] ?? '', [
        'project_post_comment', 'chat', 'project_doc', 'task_comment'
    ], true) ? $body['context_type'] : 'chat';
    $ctxId       = (int)($body['context_id'] ?? 0);
    $isTyping    = !empty($body['is_typing']);

    if ($ctxId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'context_id required']);
        exit;
    }

    try {
        if ($isTyping) {
            // Upsert with 5-second TTL (enforced on read)
            $pdo->prepare(
                'INSERT INTO typing_indicators
                   (user_id, context_type, context_id, started_at)
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE started_at = NOW()'
            )->execute([$userId, $ctxType, $ctxId]);
        } else {
            $pdo->prepare(
                'DELETE FROM typing_indicators
                  WHERE user_id = ? AND context_type = ? AND context_id = ?'
            )->execute([$userId, $ctxType, $ctxId]);
        }
        echo json_encode(['ok' => true]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* ── GET — who is typing ─────────────────────────────────── */
$ctxType = $_GET['context_type'] ?? 'chat';
$ctxId   = (int)($_GET['context_id'] ?? 0);

if ($ctxId <= 0) {
    echo json_encode(['typing' => []]);
    exit;
}

// Expire old entries (> 5 seconds) first
try {
    $pdo->prepare(
        'DELETE FROM typing_indicators
          WHERE started_at < DATE_SUB(NOW(), INTERVAL 5 SECOND)'
    )->execute();

    $stmt = $pdo->prepare(
        'SELECT ti.user_id, u.first_name, u.last_name, u.avatar
         FROM typing_indicators ti
         JOIN users u ON u.id = ti.user_id
         WHERE ti.context_type = ?
           AND ti.context_id   = ?
           AND ti.user_id      != ?
         LIMIT 5'
    );
    $stmt->execute([$ctxType, $ctxId, $userId]);
    $typing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['typing' => $typing]);
} catch (\Throwable $e) {
    echo json_encode(['typing' => [], 'error' => $e->getMessage()]);
}
