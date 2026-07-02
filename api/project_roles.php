<?php
/**
 * project_roles.php — вакансии / открытые роли проекта
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

const ROLE_SPECS = [
    'product_manager', 'frontend', 'backend', 'designer', 'ml_engineer',
    'fullstack', 'analyst', 'qa', 'marketing', 'other',
];

function ensureProjectRolesSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_roles (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        project_id      INT NOT NULL,
        title           VARCHAR(120) NOT NULL,
        description     TEXT,
        specialization  VARCHAR(50) DEFAULT NULL,
        status          ENUM('open','filled','closed') DEFAULT 'open',
        slots           INT NOT NULL DEFAULT 1,
        filled_count    INT NOT NULL DEFAULT 0,
        position        INT NOT NULL DEFAULT 0,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_proles_project (project_id),
        KEY idx_proles_status (project_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_role_applications (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        role_id     INT NOT NULL,
        project_id  INT NOT NULL,
        user_id     INT NOT NULL,
        message     TEXT,
        status      ENUM('pending','accepted','rejected') DEFAULT 'pending',
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_role_user (role_id, user_id),
        KEY idx_pra_project (project_id),
        KEY idx_pra_status (project_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $col = $pdo->query("SHOW COLUMNS FROM project_join_requests LIKE 'role_id'")->fetch();
    if (!$col) {
        try {
            $pdo->exec('ALTER TABLE project_join_requests ADD COLUMN role_id INT DEFAULT NULL AFTER message');
        } catch (Throwable $e) {}
    }
}

function normalizeSpec(?string $spec): ?string
{
    if (!$spec) return null;
    $spec = strtolower(trim($spec));
    return in_array($spec, ROLE_SPECS, true) ? $spec : 'other';
}

function requireProjectRole(PDO $pdo, int $projectId, int $userId, array $roles): void
{
    $stmt = $pdo->prepare('SELECT permission_role FROM project_members WHERE project_id=? AND user_id=?');
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !in_array($row['permission_role'], $roles, true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Недостаточно прав']);
        exit;
    }
}

function fetchRole(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM project_roles WHERE id=?');
    $stmt->execute([$id]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$role) {
        http_response_code(404);
        echo json_encode(['error' => 'Роль не найдена']);
        exit;
    }
    return $role;
}

function fetchApplication(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('
        SELECT a.*, r.title AS role_title, r.specialization, r.slots, r.filled_count
        FROM project_role_applications a
        JOIN project_roles r ON r.id = a.role_id
        WHERE a.id=?
    ');
    $stmt->execute([$id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$app) {
        http_response_code(404);
        echo json_encode(['error' => 'Заявка не найдена']);
        exit;
    }
    return $app;
}

function fetchProjectRoles(PDO $pdo, int $projectId, ?int $userId): array
{
    $stmt = $pdo->prepare('
        SELECT id, project_id, title, description, specialization, status, slots, filled_count, position
        FROM project_roles
        WHERE project_id=?
        ORDER BY position ASC, id ASC
    ');
    $stmt->execute([$projectId]);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($userId) {
        $appStmt = $pdo->prepare('
            SELECT role_id, status FROM project_role_applications
            WHERE project_id=? AND user_id=?
        ');
        $appStmt->execute([$projectId, $userId]);
        $apps = [];
        foreach ($appStmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $apps[(int)$a['role_id']] = $a['status'];
        }
        foreach ($roles as &$r) {
            $r['my_application'] = $apps[(int)$r['id']] ?? null;
        }
        unset($r);
    }

    return $roles;
}

function acceptRoleApplication(PDO $pdo, array $app): void
{
    $projectId = (int)$app['project_id'];
    $userId    = (int)$app['user_id'];
    $roleId    = (int)$app['role_id'];
    $spec      = $app['specialization'] ?: 'other';

    $pdo->prepare("UPDATE project_role_applications SET status='accepted' WHERE id=?")->execute([(int)$app['id']]);

    $pdo->prepare("
        INSERT IGNORE INTO project_members (project_id, user_id, permission_role, specialization_role)
        VALUES (?,?, 'member', ?)
    ")->execute([$projectId, $userId, $spec]);

    $pdo->prepare('UPDATE project_roles SET filled_count = filled_count + 1 WHERE id=?')->execute([$roleId]);
    syncRoleFilledStatus($pdo, $roleId);

    try {
        $nu = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id=?');
        $nu->execute([$userId]);
        $u = $nu->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            $meta = $name . ' присоединился как «' . ($app['role_title'] ?? 'участник') . '»';
            $pdo->prepare("INSERT INTO project_activity (project_id, user_id, type, meta) VALUES (?,?, 'joined', ?)")
                ->execute([$projectId, $userId, $meta]);
        }
    } catch (Throwable $e) {}
}

function syncRoleFilledStatus(PDO $pdo, int $roleId): void
{
    $stmt = $pdo->prepare('SELECT slots, filled_count, status FROM project_roles WHERE id=?');
    $stmt->execute([$roleId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r || $r['status'] === 'closed') return;

    if ((int)$r['filled_count'] >= (int)$r['slots']) {
        $pdo->prepare("UPDATE project_roles SET status='filled' WHERE id=?")->execute([$roleId]);
    } elseif ($r['status'] === 'filled') {
        $pdo->prepare("UPDATE project_roles SET status='open' WHERE id=?")->execute([$roleId]);
    }
}

function attachOpenRolesToProjects(PDO $pdo, array &$projects): void
{
    if (!$projects) return;
    ensureProjectRolesSchema($pdo);
    $ids = array_map(fn($p) => (int)$p['id'], $projects);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT id, project_id, title, status, slots, filled_count
        FROM project_roles
        WHERE project_id IN ($placeholders) AND status = 'open'
        ORDER BY position ASC
    ");
    $stmt->execute($ids);
    $byProject = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $byProject[(int)$row['project_id']][] = $row;
    }

    foreach ($projects as &$p) {
        $open = $byProject[(int)$p['id']] ?? [];
        $p['open_roles']       = $open;
        $p['open_roles_count'] = count($open);
    }
    unset($p);
}

if (mfIsDirectScript(__FILE__)) {
try {
    ensureProjectRolesSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) {
            http_response_code(400);
            echo json_encode(['error' => 'project_id required']);
            exit;
        }

        $userId = verifyTokenSoft();

        if (!empty($_GET['applications'])) {
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Требуется авторизация']);
                exit;
            }
            requireProjectRole($pdo, $projectId, $userId, ['owner', 'admin']);

            $stmt = $pdo->prepare("
                SELECT a.id, a.role_id, a.user_id, a.message, a.status, a.created_at,
                       r.title AS role_title,
                       u.first_name, u.last_name, u.avatar
                FROM project_role_applications a
                JOIN project_roles r ON r.id = a.role_id
                JOIN users u ON u.id = a.user_id
                WHERE a.project_id = ? AND a.status = 'pending'
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$projectId]);
            echo json_encode(['applications' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $roles = fetchProjectRoles($pdo, $projectId, $userId);
        echo json_encode(['roles' => $roles], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? '';

        if ($action === 'create') {
            $projectId = (int)($data['project_id'] ?? 0);
            requireProjectRole($pdo, $projectId, $userId, ['owner', 'admin']);

            $title = trim($data['title'] ?? '');
            if ($title === '' || mb_strlen($title) > 120) {
                http_response_code(400);
                echo json_encode(['error' => 'Укажите название роли (до 120 символов)']);
                exit;
            }

            $spec = normalizeSpec($data['specialization'] ?? null);
            $slots = max(1, min(20, (int)($data['slots'] ?? 1)));
            $pos = (int)$pdo->query("SELECT COALESCE(MAX(position),0)+1 FROM project_roles WHERE project_id=" . (int)$projectId)->fetchColumn();

            $pdo->prepare("
                INSERT INTO project_roles (project_id, title, description, specialization, status, slots, position)
                VALUES (?,?,?,?, 'open', ?, ?)
            ")->execute([
                $projectId,
                $title,
                trim($data['description'] ?? ''),
                $spec,
                $slots,
                $pos,
            ]);

            echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($data['id'] ?? 0);
            $role = fetchRole($pdo, $id);
            requireProjectRole($pdo, (int)$role['project_id'], $userId, ['owner', 'admin']);

            $status = $data['status'] ?? $role['status'];
            if (!in_array($status, ['open', 'filled', 'closed'], true)) {
                $status = $role['status'];
            }

            $pdo->prepare("
                UPDATE project_roles
                SET title=?, description=?, specialization=?, status=?, slots=?
                WHERE id=?
            ")->execute([
                trim($data['title'] ?? $role['title']),
                trim($data['description'] ?? $role['description'] ?? ''),
                normalizeSpec($data['specialization'] ?? $role['specialization']),
                $status,
                max(1, min(20, (int)($data['slots'] ?? $role['slots']))),
                $id,
            ]);

            syncRoleFilledStatus($pdo, $id);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($data['id'] ?? 0);
            $role = fetchRole($pdo, $id);
            requireProjectRole($pdo, (int)$role['project_id'], $userId, ['owner', 'admin']);
            $pdo->prepare('DELETE FROM project_roles WHERE id=?')->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'bulk_create') {
            $projectId = (int)($data['project_id'] ?? 0);
            requireProjectRole($pdo, $projectId, $userId, ['owner', 'admin']);
            $items = $data['roles'] ?? [];
            if (!is_array($items) || !$items) {
                http_response_code(400);
                echo json_encode(['error' => 'roles required']);
                exit;
            }
            $created = [];
            $pos = (int)$pdo->query("SELECT COALESCE(MAX(position),0) FROM project_roles WHERE project_id=" . (int)$projectId)->fetchColumn();
            foreach (array_slice($items, 0, 10) as $item) {
                $title = trim($item['title'] ?? '');
                if ($title === '') continue;
                $pos++;
                $pdo->prepare("
                    INSERT INTO project_roles (project_id, title, description, specialization, status, slots, position)
                    VALUES (?,?,?,?, 'open', 1, ?)
                ")->execute([
                    $projectId,
                    mb_substr($title, 0, 120),
                    trim($item['description'] ?? ''),
                    normalizeSpec($item['specialization'] ?? null),
                    $pos,
                ]);
                $created[] = (int)$pdo->lastInsertId();
            }
            echo json_encode(['success' => true, 'created' => $created]);
            exit;
        }

        if ($action === 'apply') {
            $roleId = (int)($data['role_id'] ?? 0);
            $role   = fetchRole($pdo, $roleId);
            $projectId = (int)$role['project_id'];

            if ($role['status'] !== 'open') {
                http_response_code(400);
                echo json_encode(['error' => 'Эта роль закрыта']);
                exit;
            }

            $chk = $pdo->prepare('SELECT id FROM project_members WHERE project_id=? AND user_id=?');
            $chk->execute([$projectId, $userId]);
            if ($chk->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Вы уже в команде']);
                exit;
            }

            if ((int)$role['filled_count'] >= (int)$role['slots']) {
                http_response_code(400);
                echo json_encode(['error' => 'Все места заняты']);
                exit;
            }

            $message = trim($data['message'] ?? '');
            $pdo->prepare("
                INSERT INTO project_role_applications (role_id, project_id, user_id, message, status)
                VALUES (?,?,?,?, 'pending')
                ON DUPLICATE KEY UPDATE message=VALUES(message), status='pending', updated_at=CURRENT_TIMESTAMP
            ")->execute([$roleId, $projectId, $userId, $message]);

            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'handle_application') {
            $appId    = (int)($data['application_id'] ?? 0);
            $decision = $data['decision'] ?? '';

            $app = fetchApplication($pdo, $appId);
            requireProjectRole($pdo, (int)$app['project_id'], $userId, ['owner', 'admin']);

            if ($decision === 'accept') {
                acceptRoleApplication($pdo, $app);
            } elseif ($decision === 'reject') {
                $pdo->prepare("UPDATE project_role_applications SET status='rejected' WHERE id=?")->execute([$appId]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'decision must be accept or reject']);
                exit;
            }

            echo json_encode(['success' => true]);
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
}
