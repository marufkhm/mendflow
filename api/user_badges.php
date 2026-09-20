<?php
/**
 * user_badges.php — achievement badges for profiles
 * GET /user_badges.php?user_id=42
 * GET /user_badges.php?user_ids=1,2,3  (batch, max 100)
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

function jsonOut($data, $code = 200)
{
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonOut(['error' => 'Method not allowed'], 405);
}

require_once 'db.php';
require_once __DIR__ . '/badges_lib.php';

try {
    $userIdsRaw = trim($_GET['user_ids'] ?? '');
    if ($userIdsRaw !== '') {
        $ids = array_values(array_filter(array_map('intval', explode(',', $userIdsRaw))));
        $ids = array_slice(array_unique($ids), 0, 100);
        if (!$ids) {
            jsonOut(['error' => 'user_ids required'], 400);
        }
        $map = mfComputeUserBadgesBatch($pdo, $ids);
        $users = [];
        foreach ($ids as $id) {
            $users[(string)$id] = $map[$id] ?? mfBuildBadgePayload([]);
        }
        jsonOut(['users' => $users]);
    }

    $userId = (int)($_GET['user_id'] ?? 0);
    if (!$userId) {
        jsonOut(['error' => 'user_id required'], 400);
    }

    $st = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    if (!$st->fetchColumn()) {
        jsonOut(['error' => 'User not found'], 404);
    }

    jsonOut(['badges' => mfComputeUserBadges($pdo, $userId)]);
} catch (Throwable $e) {
    jsonOut(['error' => 'DB error: ' . $e->getMessage()], 500);
}
