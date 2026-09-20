<?php
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

function jsonOut($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once 'db.php';

try {
    if (isset($_GET['count'])) {
        $row = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        jsonOut(['count' => (int)$row]);
    } else {
        $rows = $pdo->query("SELECT id, first_name, last_name, avatar, is_verified FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
        jsonOut(['users' => $rows]);
    }
} catch (PDOException $e) {
    jsonOut(['error' => $e->getMessage()], 500);
}