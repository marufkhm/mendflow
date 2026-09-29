<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Moderator-Password');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

require_once 'db.php';
require_once 'moderation_lib.php';

function adminJson($data, $code = 200): void
{
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function adminRequirePlatformAdmin(int $userId): void
{
    if (!canPlatformAdmin($userId)) {
        adminJson(['error' => 'Forbidden'], 403);
    }
}

try {
    global $pdo;
    ensureReportsSchema();
    ensureModerationLogSchema();
    ensurePlatformBanSchema();

    $method = $_SERVER['REQUEST_METHOD'];
    $userId = verifyToken();

    if ($method === 'GET') {
        $action = trim($_GET['action'] ?? 'access');

        if ($action === 'access') {
            $isAdmin = canPlatformAdmin($userId);
            $newCount = 0;
            if ($isAdmin) {
                try {
                    $newCount = (int)$pdo->query(
                        "SELECT COUNT(*) FROM reports WHERE status = 'new'"
                    )->fetchColumn();
                } catch (Throwable $e) {}
            }
            adminJson([
                'platform_admin'          => $isAdmin,
                'new_reports_count'       => $newCount,
                'panel_password_required' => $isAdmin && mfModeratorPanelPasswordConfigured(),
            ]);
        }

        adminRequirePlatformAdmin($userId);
        mfRequireModeratorPanelPassword();

        if ($action === 'stats') {
            adminJson(['stats' => mfAdminStats($pdo)]);
        }

        if ($action === 'users') {
            $q      = trim($_GET['q'] ?? '');
            $limit  = min(100, max(1, (int)($_GET['limit'] ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            $cols   = tableColumns('users');

            $select = ['id', 'first_name', 'last_name', 'email', 'created_at'];
            foreach (['role', 'is_admin', 'is_platform_admin', 'is_banned', 'banned_at', 'ban_reason', 'is_deleted'] as $c) {
                if (in_array($c, $cols, true)) {
                    $select[] = $c;
                }
            }
            $select = array_values(array_unique($select));

            $where  = '1=1';
            $params = [];
            if ($q !== '') {
                $where .= ' AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, " ", last_name) LIKE ? OR id = ?)';
                $like = '%' . $q . '%';
                $params = [$like, $like, $like, $like, ctype_digit($q) ? (int)$q : -1];
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE {$where}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sql = 'SELECT ' . implode(', ', $select) . " FROM users WHERE {$where} ORDER BY id DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                $row['id'] = (int)$row['id'];
                if (isset($row['is_admin'])) $row['is_admin'] = (int)$row['is_admin'];
                if (isset($row['is_platform_admin'])) $row['is_platform_admin'] = (int)$row['is_platform_admin'];
                if (isset($row['is_banned'])) $row['is_banned'] = (int)$row['is_banned'];
                if (isset($row['is_deleted'])) $row['is_deleted'] = (int)$row['is_deleted'];
                if (isset($row['created_at'])) $row['created_at'] = utcDate($row['created_at']);
                if (isset($row['banned_at'])) $row['banned_at'] = utcDate($row['banned_at']);
            }
            unset($row);

            adminJson(['users' => $rows, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
        }

        if ($action === 'logs') {
            $limit  = min(200, max(1, (int)($_GET['limit'] ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            $stmt = $pdo->prepare("
                SELECT l.id, l.admin_id, l.action, l.target_type, l.target_id, l.details, l.created_at,
                       u.first_name AS admin_first_name, u.last_name AS admin_last_name, u.email AS admin_email
                FROM moderation_logs l
                LEFT JOIN users u ON u.id = l.admin_id
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                $row['id']        = (int)$row['id'];
                $row['admin_id']  = (int)$row['admin_id'];
                $row['target_id'] = $row['target_id'] !== null ? (int)$row['target_id'] : null;
                $row['created_at'] = utcDate($row['created_at'] ?? null);
            }
            unset($row);

            adminJson(['logs' => $rows, 'limit' => $limit, 'offset' => $offset]);
        }

        adminJson(['error' => 'Unknown action'], 400);
    }

    if ($method === 'POST') {
        adminRequirePlatformAdmin($userId);
        $data   = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = trim($data['action'] ?? '');

        if ($action === 'verify_panel') {
            if (!mfModeratorPanelPasswordConfigured()) {
                adminJson(['error' => 'MODERATOR_PANEL_PASSWORD не настроен на сервере'], 503);
            }
            $pw = (string)($data['password'] ?? '');
            if (!mfVerifyModeratorPanelPassword($pw)) {
                adminJson(['error' => 'Неверный пароль'], 403);
            }
            adminJson(['success' => true]);
        }

        mfRequireModeratorPanelPassword();

        if ($action === 'ban') {
            $targetId = (int)($data['user_id'] ?? 0);
            $reason   = trim($data['reason'] ?? '');
            if (!$targetId) {
                adminJson(['error' => 'user_id required'], 400);
            }
            if (!mfPlatformBanUser($pdo, $userId, $targetId, $reason ?: null)) {
                adminJson(['error' => 'Cannot ban user'], 400);
            }
            adminJson(['success' => true, 'message' => 'User banned']);
        }

        if ($action === 'unban') {
            $targetId = (int)($data['user_id'] ?? 0);
            if (!$targetId) {
                adminJson(['error' => 'user_id required'], 400);
            }
            if (!mfPlatformUnbanUser($pdo, $userId, $targetId)) {
                adminJson(['error' => 'Cannot unban user'], 400);
            }
            adminJson(['success' => true, 'message' => 'User unbanned']);
        }

        if ($action === 'block_user') {
            $targetId = (int)($data['user_id'] ?? 0);
            if (!$targetId) {
                adminJson(['error' => 'user_id required'], 400);
            }
            if (!mfAdminBlockUser($pdo, $userId, $targetId)) {
                adminJson(['error' => 'Cannot block user'], 400);
            }
            adminJson(['success' => true, 'message' => 'User blocked by admin']);
        }

        adminJson(['error' => 'Unknown action'], 400);
    }

    adminJson(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    adminJson(['error' => 'Server error'], 500);
}
