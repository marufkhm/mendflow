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

function friendsJson($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function acceptFriendRequest(PDO $pdo, int $requestId, int $currentUserId): array {
    $stmt = $pdo->prepare('SELECT from_id, to_id FROM friend_requests WHERE id = ? AND status = "pending"');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request || (int)$request['to_id'] !== $currentUserId) {
        return ['ok' => false, 'error' => 'Not authorized', 'code' => 403];
    }

    $pdo->prepare('UPDATE friend_requests SET status = "accepted" WHERE id = ?')
        ->execute([$requestId]);

    $pdo->prepare(
        'INSERT INTO friendships (sender_id, receiver_id, status, created_at, updated_at)
         VALUES (?, ?, "accepted", NOW(), NOW())
         ON DUPLICATE KEY UPDATE status = "accepted", updated_at = NOW()'
    )->execute([(int)$request['from_id'], (int)$request['to_id']]);

    return ['ok' => true, 'message' => 'Request accepted'];
}

try {
    $currentUserId = verifyToken();
    global $pdo;
    if (function_exists('ensureFriendshipsSchema')) {
        ensureFriendshipsSchema();
    }
    $method = $_SERVER['REQUEST_METHOD'];
    $type = $_GET['type'] ?? null;

    // ====================== GET ======================
    if ($method === 'GET') {

        if ($type === 'incoming') {
            $stmt = $pdo->prepare(
                'SELECT fr.id, fr.from_id, u.first_name, u.last_name, u.avatar, u.is_verified, fr.created_at
                 FROM friend_requests fr
                 JOIN users u ON fr.from_id = u.id
                 WHERE fr.to_id = ? AND fr.status = "pending"
                 ORDER BY fr.created_at DESC'
            );
            $stmt->execute([$currentUserId]);
            friendsJson(['requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        if ($type === 'outgoing') {
            $stmt = $pdo->prepare(
                'SELECT fr.id, fr.to_id, fr.to_id AS receiver_id, u.first_name, u.last_name, u.avatar, fr.created_at
                 FROM friend_requests fr
                 JOIN users u ON fr.to_id = u.id
                 WHERE fr.from_id = ? AND fr.status = "pending"
                 ORDER BY fr.created_at DESC'
            );
            $stmt->execute([$currentUserId]);
            friendsJson(['requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        if ($type === 'friends') {
            // Support user_id param: returns friends of that user (public list)
            $targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentUserId;
            $stmt = $pdo->prepare(
                'SELECT u.id, u.first_name, u.last_name, u.avatar, u.is_verified
                 FROM friendships f
                 JOIN users u ON (
                     (f.sender_id = ? AND f.receiver_id = u.id) OR
                     (f.receiver_id = ? AND f.sender_id = u.id)
                 )
                 WHERE f.status = "accepted"
                 ORDER BY u.first_name ASC'
            );
            $stmt->execute([$targetId, $targetId]);
            friendsJson(['friends' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        if ($type === 'count') {
            $userId = (int)($_GET['user_id'] ?? $currentUserId);
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS friend_count
                 FROM friendships
                 WHERE status = "accepted"
                   AND (sender_id = ? OR receiver_id = ?)'
            );
            $stmt->execute([$userId, $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            friendsJson(['friend_count' => (int)$result['friend_count']]);
        }

        if ($type === 'recommendations') {
            require_once __DIR__ . '/friend_recommendations.php';
            $limit = (int)($_GET['limit'] ?? 12);
            friendsJson(frGetRecommendations($pdo, $currentUserId, $limit));
        }
    }

    // ====================== POST ======================
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $data['action'] ?? null;

        // Отправить заявку (или принять входящую, если уже есть)
        if ($action === 'send') {
            $toId = (int)($data['to_id'] ?? 0);
            if (!$toId || $toId === $currentUserId) {
                friendsJson(['error' => 'Invalid to_id'], 400);
            }

            $stmt = $pdo->prepare(
                'SELECT id FROM friendships
                 WHERE status = "accepted" AND (
                     (sender_id = ? AND receiver_id = ?) OR
                     (receiver_id = ? AND sender_id = ?)
                 )'
            );
            $stmt->execute([$currentUserId, $toId, $currentUserId, $toId]);
            if ($stmt->fetch()) {
                friendsJson(['error' => 'Already friends'], 400);
            }

            // Входящая заявка от этого пользователя — сразу принимаем
            $stmt = $pdo->prepare(
                'SELECT id FROM friend_requests
                 WHERE from_id = ? AND to_id = ? AND status = "pending"
                 LIMIT 1'
            );
            $stmt->execute([$toId, $currentUserId]);
            $incomingId = (int)$stmt->fetchColumn();
            if ($incomingId) {
                $result = acceptFriendRequest($pdo, $incomingId, $currentUserId);
                if (!$result['ok']) {
                    friendsJson(['error' => $result['error']], $result['code']);
                }
                friendsJson(['success' => true, 'message' => 'Request accepted', 'accepted' => true]);
            }

            // Уже отправленная заявка
            $stmt = $pdo->prepare(
                'SELECT id, status FROM friend_requests
                 WHERE from_id = ? AND to_id = ?
                 LIMIT 1'
            );
            $stmt->execute([$currentUserId, $toId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing['status'] === 'pending') {
                    friendsJson(['error' => 'Request already sent'], 400);
                }
                // Были друзья / заявка отклонена — отправляем снова
                $pdo->prepare(
                    'UPDATE friend_requests SET status = "pending", created_at = NOW() WHERE id = ?'
                )->execute([(int)$existing['id']]);
                friendsJson(['success' => true, 'message' => 'Request sent']);
            }

            $pdo->prepare(
                'INSERT INTO friend_requests (from_id, to_id, status, created_at)
                 VALUES (?, ?, "pending", NOW())'
            )->execute([$currentUserId, $toId]);

            friendsJson(['success' => true, 'message' => 'Request sent']);
        }

        if ($action === 'accept') {
            $requestId = (int)($data['request_id'] ?? 0);
            if (!$requestId) {
                friendsJson(['error' => 'Missing request_id'], 400);
            }
            $result = acceptFriendRequest($pdo, $requestId, $currentUserId);
            if (!$result['ok']) {
                friendsJson(['error' => $result['error']], $result['code']);
            }
            friendsJson(['success' => true, 'message' => $result['message']]);
        }

        if ($action === 'reject') {
            $requestId = (int)($data['request_id'] ?? 0);
            if (!$requestId) {
                friendsJson(['error' => 'Missing request_id'], 400);
            }

            $stmt = $pdo->prepare('SELECT to_id FROM friend_requests WHERE id = ?');
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request || (int)$request['to_id'] !== $currentUserId) {
                friendsJson(['error' => 'Not authorized'], 403);
            }

            $pdo->prepare('UPDATE friend_requests SET status = "rejected" WHERE id = ?')
                ->execute([$requestId]);

            friendsJson(['success' => true, 'message' => 'Request rejected']);
        }

        if ($action === 'unfriend') {
            $userId = (int)($data['user_id'] ?? 0);
            if (!$userId) {
                friendsJson(['error' => 'Missing user_id'], 400);
            }

            $pdo->prepare(
                'UPDATE friendships
                 SET status = "rejected", updated_at = NOW()
                 WHERE (sender_id = ? AND receiver_id = ?)
                    OR (receiver_id = ? AND sender_id = ?)'
            )->execute([$currentUserId, $userId, $currentUserId, $userId]);

            // Сбрасываем старые заявки, чтобы можно было добавить снова
            $pdo->prepare(
                'UPDATE friend_requests SET status = "rejected"
                 WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?)'
            )->execute([$currentUserId, $userId, $userId, $currentUserId]);

            friendsJson(['success' => true, 'message' => 'Removed from friends']);
        }
    }

    friendsJson(['error' => 'Invalid request'], 400);

} catch (Throwable $e) {
    friendsJson(['error' => 'Server error: ' . $e->getMessage()], 500);
}
