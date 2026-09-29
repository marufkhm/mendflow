<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

require_once 'db.php';

function blocksJson($data, $code = 200): void
{
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $currentUserId = verifyToken();
    global $pdo;
    ensureBlocksSchema();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $type = trim($_GET['type'] ?? 'list');

        if ($type === 'ids') {
            blocksJson(['blocked_ids' => mfBlockedUserIds($pdo, $currentUserId)]);
        }

        if ($type === 'check') {
            $otherId = (int)($_GET['user_id'] ?? 0);
            if (!$otherId) {
                blocksJson(['error' => 'user_id required'], 400);
            }
            $iBlocked = false;
            $blockedMe = false;
            try {
                $st = $pdo->prepare('SELECT 1 FROM user_blocks WHERE blocker_id = ? AND blocked_id = ? LIMIT 1');
                $st->execute([$currentUserId, $otherId]);
                $iBlocked = (bool)$st->fetchColumn();
                $st->execute([$otherId, $currentUserId]);
                $blockedMe = (bool)$st->fetchColumn();
            } catch (Throwable $e) {}
            blocksJson([
                'blocked'    => $iBlocked || $blockedMe,
                'i_blocked'  => $iBlocked,
                'blocked_me' => $blockedMe,
            ]);
        }

        // type=list — пользователи, которых заблокировал текущий пользователь
        $stmt = $pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.avatar, u.is_verified, ub.created_at
             FROM user_blocks ub
             JOIN users u ON u.id = ub.blocked_id
             WHERE ub.blocker_id = ?
             ORDER BY ub.created_at DESC'
        );
        $stmt->execute([$currentUserId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['id']          = (int)$row['id'];
            $row['is_verified'] = !empty($row['is_verified']);
            $row['created_at']  = utcDate($row['created_at'] ?? null);
            if (!empty($row['avatar'])) {
                $row['avatar'] = normalizeMediaUrl($row['avatar']);
            }
        }
        unset($row);
        blocksJson(['blocked' => $rows]);
    }

    if ($method === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = trim($data['action'] ?? '');
        $userId = (int)($data['user_id'] ?? 0);

        if (!$userId) {
            blocksJson(['error' => 'user_id required'], 400);
        }
        if ($userId === $currentUserId) {
            blocksJson(['error' => 'Cannot block yourself'], 400);
        }

        $st = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        if (!$st->fetchColumn()) {
            blocksJson(['error' => 'User not found'], 404);
        }

        if ($action === 'block') {
            $pdo->prepare(
                'INSERT IGNORE INTO user_blocks (blocker_id, blocked_id, created_at)
                 VALUES (?, ?, UTC_TIMESTAMP())'
            )->execute([$currentUserId, $userId]);
            mfClearFriendshipBetween($pdo, $currentUserId, $userId);
            blocksJson(['success' => true, 'message' => 'User blocked']);
        }

        if ($action === 'unblock') {
            $pdo->prepare(
                'DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?'
            )->execute([$currentUserId, $userId]);
            blocksJson(['success' => true, 'message' => 'User unblocked']);
        }

        blocksJson(['error' => 'Unknown action'], 400);
    }

    blocksJson(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    blocksJson(['error' => 'Server error'], 500);
}
