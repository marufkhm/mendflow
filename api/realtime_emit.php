<?php
/**
 * Realtime Event Emitter
 * Sprint 5 — used by other API endpoints to push events to SSE subscribers
 *
 * Usage:
 *   require_once __DIR__ . '/realtime_emit.php';
 *   realtimeEmit($pdo, 'project_post', $projectId, $payload);
 *
 * The SSE loop in sse.php polls this table for new rows.
 */

declare(strict_types=1);

/**
 * @param PDO    $pdo
 * @param string $eventType  'project_post' | 'task_update' | 'global_post' | 'comment_added'
 * @param int|null $projectId  null for global events
 * @param array  $payload
 */
function realtimeEmit(PDO $pdo, string $eventType, ?int $projectId, array $payload): void
{
    try {
        $pdo->prepare(
            'INSERT INTO realtime_events (event_type, project_id, payload, created_at)
             VALUES (?, ?, ?, NOW())'
        )->execute([
            $eventType,
            $projectId,
            json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    } catch (\Throwable) {
        // Non-critical — do not break parent request
    }
}

/**
 * Prune old realtime_events (call from a cron or lazy-cleanup)
 * Keeps last 24 hours only
 */
function realtimePrune(PDO $pdo): void
{
    try {
        $pdo->prepare(
            "DELETE FROM realtime_events
              WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        )->execute();
    } catch (\Throwable) {}
}
