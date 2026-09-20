<?php
/**
 * SSE — Server-Sent Events endpoint
 * Sprint 5: Realtime + Collaboration
 *
 * GET /api/sse.php?project_id=123&channel=project
 *
 * Channels:
 *   project  — project feed, tasks, comments, presence
 *   global   — feed updates, notifications
 *
 * Requires:
 *   - auth.php (Bearer token middleware)
 *   - db.php   (PDO $pdo)
 */

declare(strict_types=1);
require_once __DIR__ . '/auth.php';   // sets $authUser or sends 401
require_once __DIR__ . '/db.php';     // provides $pdo

/* ── Headers ─────────────────────────────────────────────── */
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-store');
header('X-Accel-Buffering: no');       // Nginx: disable buffering
header('Connection: keep-alive');

// Prevent output buffering at every layer
@ini_set('output_buffering', '0');
@ini_set('zlib.output_compression', '0');
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}

/* ── Params ──────────────────────────────────────────────── */
$userId    = (int) $authUser['id'];
$channel   = in_array($_GET['channel'] ?? 'global', ['project', 'global'], true)
             ? ($_GET['channel'] ?? 'global')
             : 'global';
$projectId = ($channel === 'project') ? (int)($_GET['project_id'] ?? 0) : 0;

if ($channel === 'project' && $projectId <= 0) {
    http_response_code(400);
    echo "data: " . json_encode(['error' => 'project_id required']) . "\n\n";
    exit;
}

/* ── Verify project membership (for project channel) ────── */
if ($channel === 'project') {
    $stmt = $pdo->prepare(
        'SELECT permission_role FROM project_members
          WHERE project_id = ? AND user_id = ?
          LIMIT 1'
    );
    $stmt->execute([$projectId, $userId]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);
    // Viewers and up can subscribe; public projects also allowed
    // Here we allow any authenticated user (adjust if project is private)
}

/* ── Register presence ───────────────────────────────────── */
updatePresence($pdo, $userId, $projectId, 'connected');

/* ── Send initial "connected" event ─────────────────────── */
sendEvent('connected', [
    'user_id'    => $userId,
    'project_id' => $projectId,
    'channel'    => $channel,
    'ts'         => time(),
]);

/* ── State cursors ───────────────────────────────────────── */
$lastEventId   = (int)($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);
$lastTaskSync  = microtime(true);
$lastPresSync  = microtime(true);
$lastFeedSync  = microtime(true);
$lastNotifSync = microtime(true);
$lastHeartbeat = microtime(true);

$maxRuntime  = 55;   // seconds; reconnect before PHP/nginx timeout
$pollInterval = 1.5; // seconds between DB polls
$startTime   = microtime(true);

/* ── Main loop ───────────────────────────────────────────── */
while (true) {
    // Check client disconnect
    if (connection_aborted()) {
        updatePresence($pdo, $userId, $projectId, 'disconnected');
        break;
    }

    $now = microtime(true);

    // Max runtime guard — tell client to reconnect
    if ($now - $startTime >= $maxRuntime) {
        sendEvent('reconnect', ['reason' => 'max_runtime']);
        updatePresence($pdo, $userId, $projectId, 'disconnected');
        break;
    }

    /* ── Heartbeat every 20s ──────────────────────────────── */
    if ($now - $lastHeartbeat >= 20) {
        sendEvent('heartbeat', ['ts' => time()]);
        $lastHeartbeat = $now;
    }

    /* ── Project channel events ───────────────────────────── */
    if ($channel === 'project' && $projectId > 0) {

        // Feed posts (new posts since last check)
        if ($now - $lastFeedSync >= $pollInterval) {
            $newPosts = fetchNewProjectPosts($pdo, $projectId, $lastEventId);
            foreach ($newPosts as $post) {
                $lastEventId = max($lastEventId, (int)$post['event_cursor']);
                sendEvent('project_post', $post, (string)$lastEventId);
            }
            $lastFeedSync = $now;
        }

        // Task updates
        if ($now - $lastTaskSync >= $pollInterval) {
            $taskUpdates = fetchTaskUpdates($pdo, $projectId, $lastEventId);
            foreach ($taskUpdates as $upd) {
                $lastEventId = max($lastEventId, (int)$upd['event_cursor']);
                sendEvent('task_update', $upd, (string)$lastEventId);
            }
            $lastTaskSync = $now;
        }

        // Presence (who's online in this project)
        if ($now - $lastPresSync >= 4) {
            $presence = fetchProjectPresence($pdo, $projectId);
            sendEvent('presence', ['users' => $presence]);
            $lastPresSync = $now;
        }
    }

    /* ── Global channel events ────────────────────────────── */
    if ($channel === 'global') {

        if ($now - $lastFeedSync >= $pollInterval) {
            $newPosts = fetchNewGlobalPosts($pdo, $userId, $lastEventId);
            foreach ($newPosts as $post) {
                $lastEventId = max($lastEventId, (int)$post['event_cursor']);
                sendEvent('global_post', $post, (string)$lastEventId);
            }
            $lastFeedSync = $now;
        }

        if ($now - $lastNotifSync >= 5) {
            $notifs = fetchNewNotifications($pdo, $userId, $lastEventId);
            foreach ($notifs as $n) {
                $lastEventId = max($lastEventId, (int)$n['event_cursor']);
                sendEvent('notification', $n, (string)$lastEventId);
            }
            $lastNotifSync = $now;
        }
    }

    usleep(500_000); // 0.5s
    flush();
    if (ob_get_level()) ob_flush();
}

/* ════════════════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════════════════ */

function sendEvent(string $type, array $data, string $id = ''): void
{
    if ($id !== '') {
        echo "id: {$id}\n";
    }
    echo "event: {$type}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    flush();
    if (ob_get_level()) ob_flush();
}

function updatePresence(PDO $pdo, int $userId, int $projectId, string $status): void
{
    try {
        $pdo->prepare(
            'INSERT INTO realtime_presence (user_id, project_id, status, last_seen)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), last_seen = NOW()'
        )->execute([$userId, $projectId, $status]);
    } catch (\Throwable) {}
}

/**
 * Fetch new project feed posts added after the cursor.
 * Uses realtime_events table populated by a DB trigger or post-create hook.
 */
function fetchNewProjectPosts(PDO $pdo, int $projectId, int $cursor): array
{
    $stmt = $pdo->prepare(
        'SELECT re.id AS event_cursor, re.payload
         FROM realtime_events re
         WHERE re.project_id = ?
           AND re.event_type = "project_post"
           AND re.id > ?
         ORDER BY re.id ASC
         LIMIT 10'
    );
    $stmt->execute([$projectId, $cursor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function ($row) {
        $payload = json_decode($row['payload'], true) ?? [];
        $payload['event_cursor'] = $row['event_cursor'];
        return $payload;
    }, $rows);
}

function fetchTaskUpdates(PDO $pdo, int $projectId, int $cursor): array
{
    $stmt = $pdo->prepare(
        'SELECT re.id AS event_cursor, re.payload
         FROM realtime_events re
         WHERE re.project_id = ?
           AND re.event_type = "task_update"
           AND re.id > ?
         ORDER BY re.id ASC
         LIMIT 20'
    );
    $stmt->execute([$projectId, $cursor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function ($row) {
        $payload = json_decode($row['payload'], true) ?? [];
        $payload['event_cursor'] = $row['event_cursor'];
        return $payload;
    }, $rows);
}

function fetchProjectPresence(PDO $pdo, int $projectId): array
{
    $stmt = $pdo->prepare(
        'SELECT rp.user_id, rp.status, rp.editing_context,
                u.first_name, u.last_name, u.avatar
         FROM realtime_presence rp
         JOIN users u ON u.id = rp.user_id
         WHERE rp.project_id = ?
           AND rp.last_seen >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
         ORDER BY rp.last_seen DESC'
    );
    $stmt->execute([$projectId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchNewGlobalPosts(PDO $pdo, int $userId, int $cursor): array
{
    $stmt = $pdo->prepare(
        'SELECT re.id AS event_cursor, re.payload
         FROM realtime_events re
         WHERE re.project_id IS NULL
           AND re.event_type = "global_post"
           AND re.id > ?
         ORDER BY re.id ASC
         LIMIT 5'
    );
    $stmt->execute([$cursor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function ($row) {
        $payload = json_decode($row['payload'], true) ?? [];
        $payload['event_cursor'] = $row['event_cursor'];
        return $payload;
    }, $rows);
}

function fetchNewNotifications(PDO $pdo, int $userId, int $cursor): array
{
    $stmt = $pdo->prepare(
        'SELECT n.id AS event_cursor,
                n.type, n.is_read,
                n.created_at,
                u.first_name, u.last_name, u.avatar,
                p.content AS post_preview
         FROM notifications n
         JOIN users u ON u.id = n.from_user_id
         LEFT JOIN posts p ON p.id = n.post_id
         WHERE n.user_id = ?
           AND n.id > ?
         ORDER BY n.id ASC
         LIMIT 10'
    );
    $stmt->execute([$userId, $cursor]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
