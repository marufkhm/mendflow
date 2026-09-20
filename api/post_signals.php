<?php
/**
 * post_signals.php — сбор сигналов ленты (impression, read time, watch time, DM share)
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once __DIR__ . '/feed_ranking.php';

try {
    ensureFeedRankingSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? 'batch';

        if ($action === 'dm_share') {
            $postId = (int)($data['post_id'] ?? 0);
            $toId   = (int)($data['to_user_id'] ?? 0);
            if (!$postId || !$toId) {
                http_response_code(400);
                echo json_encode(['error' => 'post_id и to_user_id обязательны']);
                exit;
            }
            frRecordDmShare($pdo, $postId, $userId, $toId);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'batch') {
            $signals = $data['signals'] ?? [];
            if (!is_array($signals) || !$signals) {
                http_response_code(400);
                echo json_encode(['error' => 'signals required']);
                exit;
            }
            frRecordSignals($pdo, $userId, array_slice($signals, 0, 40));
            echo json_encode(['success' => true, 'count' => count($signals)]);
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
