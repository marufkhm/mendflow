<?php
/**
 * MENDFLOW — projects.php
 * Sprint 1+2: Core Projects API
 * Handles: CRUD projects, members, join requests, followers
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once __DIR__ . '/project_roles.php';
require_once __DIR__ . '/project_templates.php';

function ensureProjectsSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        owner_id      INT NOT NULL,
        title         VARCHAR(200) NOT NULL,
        description   TEXT,
        cover_url     VARCHAR(500) DEFAULT NULL,
        category      ENUM('product','research','design','ai_ml','mobile','web','other') DEFAULT 'other',
        stage         ENUM('idea','prototype','mvp','beta','launched','completed') DEFAULT 'idea',
        tags          VARCHAR(500) DEFAULT NULL,
        is_public     TINYINT(1) DEFAULT 1,
        looking_for   VARCHAR(500) DEFAULT NULL,
        website_url   VARCHAR(300) DEFAULT NULL,
        github_url    VARCHAR(300) DEFAULT NULL,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_projects_owner (owner_id),
        KEY idx_projects_category (category),
        KEY idx_projects_stage (stage),
        KEY idx_projects_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_members (
        id                  INT AUTO_INCREMENT PRIMARY KEY,
        project_id          INT NOT NULL,
        user_id             INT NOT NULL,
        permission_role     ENUM('owner','admin','member','viewer') DEFAULT 'member',
        specialization_role ENUM('product_manager','frontend','backend','designer','ml_engineer','analyst','qa','marketing','fullstack','other') DEFAULT 'other',
        joined_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_member (project_id, user_id),
        KEY idx_pmembers_project (project_id),
        KEY idx_pmembers_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_join_requests (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        project_id  INT NOT NULL,
        user_id     INT NOT NULL,
        message     TEXT,
        status      ENUM('pending','accepted','rejected') DEFAULT 'pending',
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_request (project_id, user_id),
        KEY idx_pjoin_project (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $col = $pdo->query("SHOW COLUMNS FROM project_join_requests LIKE 'role_id'")->fetch();
    if (!$col) {
        try {
            $pdo->exec('ALTER TABLE project_join_requests ADD COLUMN role_id INT DEFAULT NULL AFTER message');
        } catch (Throwable $e) {}
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_followers (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        project_id  INT NOT NULL,
        user_id     INT NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (project_id, user_id),
        KEY idx_pfollowers_project (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_posts (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        project_id  INT NOT NULL,
        author_id   INT NOT NULL,
        type        ENUM('update','milestone','release','hiring','general') DEFAULT 'general',
        title       VARCHAR(300) DEFAULT NULL,
        content     TEXT NOT NULL,
        image_url   VARCHAR(500) DEFAULT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_pposts_project (project_id),
        KEY idx_pposts_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_tasks (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        project_id   INT NOT NULL,
        assignee_id  INT DEFAULT NULL,
        created_by   INT NOT NULL,
        title        VARCHAR(300) NOT NULL,
        description  TEXT,
        status       ENUM('backlog','todo','in_progress','review','done') DEFAULT 'backlog',
        priority     ENUM('low','medium','high','urgent') DEFAULT 'medium',
        due_date     DATE DEFAULT NULL,
        position     INT DEFAULT 0,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_ptasks_project (project_id),
        KEY idx_ptasks_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_activity (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        user_id    INT DEFAULT NULL,
        type       ENUM('joined','left','post','task_done','milestone','release','role_changed') DEFAULT 'post',
        meta       VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_pactivity_project (project_id),
        KEY idx_pactivity_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ensureProjectRolesSchema($pdo);
}

try {
    ensureProjectsSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];
    $action = trim($_GET['action'] ?? '');

    // ── GET routes ──────────────────────────────────────────────
    if ($method === 'GET') {
        $userId = verifyTokenSoft();

        // GET /projects.php?action=explore — public project list
        if ($action === 'explore') {
            $page     = max(1, (int)($_GET['page']  ?? 1));
            $limit    = min(30, (int)($_GET['limit'] ?? 12));
            $offset   = ($page - 1) * $limit;
            $category = $_GET['category'] ?? '';
            $stage    = $_GET['stage']    ?? '';
            $search   = trim($_GET['q']   ?? '');
            $sort     = $_GET['sort']     ?? 'newest'; // newest | trending

            $where  = ['p.is_public = 1'];
            $params = [];

            if ($category) { $where[] = 'p.category = ?'; $params[] = $category; }
            if ($stage)    { $where[] = 'p.stage = ?';    $params[] = $stage; }
            if ($search)   {
                $where[] = '(p.title LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)';
                $like = "%$search%";
                $params = array_merge($params, [$like, $like, $like]);
            }

            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $orderSQL = $sort === 'trending'
                ? 'ORDER BY member_count DESC, p.created_at DESC'
                : 'ORDER BY p.created_at DESC';

            $stmt = $pdo->prepare("
                SELECT
                    p.id, p.title, p.description, p.cover_url, p.category,
                    p.stage, p.tags, p.looking_for, p.created_at,
                    u.id AS owner_id, u.first_name, u.last_name, u.avatar,
                    (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS member_count,
                    (SELECT COUNT(*) FROM project_followers pf WHERE pf.project_id = p.id) AS follower_count,
                    (SELECT COUNT(*) FROM project_posts pp WHERE pp.project_id = p.id) AS post_count,
                    " . ($userId ? "(SELECT COUNT(*) FROM project_followers pf2 WHERE pf2.project_id = p.id AND pf2.user_id = $userId)" : "0") . " AS is_following,
                    " . ($userId ? "(SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id AND pm2.user_id = $userId)" : "0") . " AS is_member
                FROM projects p
                JOIN users u ON p.owner_id = u.id
                $whereSQL
                $orderSQL
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $projects = $stmt->fetchAll();

            attachOpenRolesToProjects($pdo, $projects);

            foreach ($projects as &$p) {
                $p['tags']         = $p['tags'] ? explode(',', $p['tags']) : [];
                $p['is_following'] = (bool)$p['is_following'];
                $p['is_member']    = (bool)$p['is_member'];
                $p['time_ago']     = timeAgo($p['created_at']);
            }

            echo json_encode(['projects' => $projects], JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));
            exit;
        }

        // GET /projects.php?action=my — user's own projects
        if ($action === 'my') {
            $userId = verifyToken();
            $stmt = $pdo->prepare("
                SELECT
                    p.id, p.title, p.description, p.cover_url, p.category, p.stage, p.tags,
                    p.created_at, p.updated_at,
                    pm.permission_role, pm.specialization_role,
                    (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) AS member_count,
                    (SELECT COUNT(*) FROM project_tasks pt WHERE pt.project_id = p.id AND pt.status != 'done') AS open_tasks,
                    (SELECT COUNT(*) FROM project_tasks pt2 WHERE pt2.project_id = p.id AND pt2.status = 'done') AS done_tasks,
                    (SELECT COUNT(*) FROM project_tasks pt3 WHERE pt3.project_id = p.id) AS total_tasks
                FROM projects p
                JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
                ORDER BY p.updated_at DESC
            ");
            $stmt->execute([$userId]);
            $projects = $stmt->fetchAll();
            foreach ($projects as &$p) {
                $p['tags']     = $p['tags'] ? explode(',', $p['tags']) : [];
                $p['time_ago'] = timeAgo($p['updated_at'] ?? $p['created_at']);
                $total = (int)$p['total_tasks'];
                $done  = (int)$p['done_tasks'];
                $p['progress'] = $total > 0 ? round($done / $total * 100) : 0;
            }
            mfJsonResponse(['projects' => $projects]);
        }

        // GET /projects.php?action=detail&id=X — single project detail
        if ($action === 'detail') {
            $projectId = (int)($_GET['id'] ?? 0);
            if (!$projectId) { http_response_code(400); echo json_encode(['error' => 'ID не указан']); exit; }

            $stmt = $pdo->prepare("
                SELECT p.*,
                    u.id AS owner_user_id, u.first_name, u.last_name, u.avatar AS owner_avatar,
                    (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS member_count,
                    (SELECT COUNT(*) FROM project_followers pf WHERE pf.project_id = p.id) AS follower_count,
                    (SELECT COUNT(*) FROM project_posts pp WHERE pp.project_id = p.id) AS post_count,
                    (SELECT COUNT(*) FROM project_tasks pt WHERE pt.project_id = p.id AND pt.status != 'done') AS open_tasks
                FROM projects p
                JOIN users u ON p.owner_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            if (!$project) { http_response_code(404); echo json_encode(['error' => 'Проект не найден']); exit; }

            // Viewer permission check
            if (!$project['is_public'] && !$userId) {
                http_response_code(403); echo json_encode(['error' => 'Нет доступа']); exit;
            }

            $project['tags']       = $project['tags'] ? explode(',', $project['tags']) : [];
            $project['time_ago']   = timeAgo($project['created_at']);

            // Current user's role
            $project['my_role'] = null;
            $project['my_spec'] = null;
            if ($userId) {
                $rs = $pdo->prepare("SELECT permission_role, specialization_role FROM project_members WHERE project_id=? AND user_id=?");
                $rs->execute([$projectId, $userId]);
                $myMem = $rs->fetch();
                if ($myMem) {
                    $project['my_role'] = $myMem['permission_role'];
                    $project['my_spec'] = $myMem['specialization_role'];
                }
                $rf = $pdo->prepare("SELECT id FROM project_followers WHERE project_id=? AND user_id=?");
                $rf->execute([$projectId, $userId]);
                $project['is_following'] = (bool)$rf->fetch();

                $rj = $pdo->prepare("SELECT status FROM project_join_requests WHERE project_id=? AND user_id=?");
                $rj->execute([$projectId, $userId]);
                $jr = $rj->fetch();
                $project['join_request_status'] = $jr ? $jr['status'] : null;
            } else {
                $project['is_following']        = false;
                $project['join_request_status'] = null;
            }

            $roles = fetchProjectRoles($pdo, $projectId, $userId);
            $project['roles'] = $roles;
            $project['open_roles'] = array_values(array_filter($roles, fn($r) => $r['status'] === 'open'));
            $project['open_roles_count'] = count($project['open_roles']);
            $project['resources'] = fetchProjectResources($pdo, $projectId);
            $project['resource_plans'] = fetchProjectResourcePlans($pdo, $projectId);

            echo json_encode(['project' => $project]);
            exit;
        }

        // GET /projects.php?action=members&id=X
        if ($action === 'members') {
            $projectId = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT
                    pm.id, pm.permission_role, pm.specialization_role, pm.joined_at,
                    u.id AS user_id, u.first_name, u.last_name, u.avatar
                FROM project_members pm
                JOIN users u ON pm.user_id = u.id
                WHERE pm.project_id = ?
                ORDER BY FIELD(pm.permission_role,'owner','admin','member','viewer'), pm.joined_at ASC
            ");
            $stmt->execute([$projectId]);
            $members = $stmt->fetchAll();
            foreach ($members as &$m) {
                $m['joined_ago'] = timeAgo($m['joined_at']);
            }
            echo json_encode(['members' => $members]);
            exit;
        }

        // GET /projects.php?action=export_readme&id=X
        if ($action === 'export_readme') {
            $userId = verifyToken();
            $projectId = (int)($_GET['id'] ?? 0);
            if (!$projectId) { http_response_code(400); echo json_encode(['error' => 'ID не указан']); exit; }

            $stmt = $pdo->prepare('SELECT id, title, is_public FROM projects WHERE id=?');
            $stmt->execute([$projectId]);
            $proj = $stmt->fetch();
            if (!$proj) { http_response_code(404); echo json_encode(['error' => 'Проект не найден']); exit; }

            if (!$proj['is_public']) {
                $m = $pdo->prepare('SELECT id FROM project_members WHERE project_id=? AND user_id=?');
                $m->execute([$projectId, $userId]);
                if (!$m->fetch()) { http_response_code(403); echo json_encode(['error' => 'Нет доступа']); exit; }
            }

            $markdown = buildProjectReadme($pdo, $projectId);
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($proj['title']));
            $slug = trim($slug, '-') ?: 'project';
            echo json_encode([
                'markdown' => $markdown,
                'filename' => $slug . '-readme.md',
            ]);
            exit;
        }

        // GET /projects.php?action=join_requests&id=X  (admin only)
        if ($action === 'join_requests') {
            $userId    = verifyToken();
            $projectId = (int)($_GET['id'] ?? 0);
            requireRole($pdo, $projectId, $userId, ['owner','admin']);
            $stmt = $pdo->prepare("
                SELECT jr.*, u.first_name, u.last_name, u.avatar,
                       pr.title AS role_title
                FROM project_join_requests jr
                JOIN users u ON jr.user_id = u.id
                LEFT JOIN project_roles pr ON pr.id = jr.role_id
                WHERE jr.project_id = ? AND jr.status = 'pending'
                ORDER BY jr.created_at DESC
            ");
            $stmt->execute([$projectId]);
            echo json_encode(['requests' => $stmt->fetchAll()]);
            exit;
        }

        // GET /projects.php?user_id=X — проекты пользователя (участник)
        $profileUserId = (int)($_GET['user_id'] ?? 0);
        if ($profileUserId && $action === '') {
            $viewerId    = verifyTokenSoft();
            $showPrivate = $viewerId && (int)$viewerId === $profileUserId;

            $sql = "
                SELECT
                    p.id, p.title, p.description, p.cover_url, p.category, p.stage, p.tags,
                    p.created_at, p.updated_at, p.is_public,
                    pm.permission_role, pm.specialization_role,
                    (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) AS member_count,
                    (SELECT COUNT(*) FROM project_followers pf WHERE pf.project_id = p.id) AS follower_count
                FROM projects p
                JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
            ";
            if (!$showPrivate) {
                $sql .= " WHERE p.is_public = 1";
            }
            $sql .= " ORDER BY p.updated_at DESC, p.created_at DESC LIMIT 30";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$profileUserId]);
            $projects = $stmt->fetchAll();
            foreach ($projects as &$p) {
                $p['tags']     = $p['tags'] ? explode(',', $p['tags']) : [];
                $p['time_ago'] = timeAgo($p['updated_at'] ?? $p['created_at']);
                $p['role']     = $p['permission_role'] ?? 'member';
            }
            unset($p);
            echo json_encode(['projects' => $projects]);
            exit;
        }

        http_response_code(400); echo json_encode(['error' => 'Неизвестный action']); exit;
    }

    // ── POST routes ─────────────────────────────────────────────
    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? $action;

        // Create project
        if ($action === 'create') {
            $title = trim($data['title'] ?? '');
            if (!$title) { http_response_code(400); echo json_encode(['error' => 'Название обязательно']); exit; }

            $tags = '';
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $tags = implode(',', array_map('trim', array_slice($data['tags'], 0, 10)));
            }

            $pdo->prepare("
                INSERT INTO projects (owner_id, title, description, cover_url, category, stage, tags, looking_for, website_url, github_url, is_public)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $userId,
                $title,
                trim($data['description'] ?? ''),
                $data['cover_url']   ?? null,
                $data['category']    ?? 'other',
                $data['stage']       ?? 'idea',
                $tags,
                $data['looking_for'] ?? null,
                $data['website_url'] ?? null,
                $data['github_url']  ?? null,
                isset($data['is_public']) ? (int)(bool)$data['is_public'] : 1,
            ]);
            $projectId = (int)$pdo->lastInsertId();

            // Auto-add owner as member
            $pdo->prepare("
                INSERT INTO project_members (project_id, user_id, permission_role, specialization_role)
                VALUES (?, ?, 'owner', ?)
            ")->execute([$projectId, $userId, $data['my_specialization'] ?? 'other']);

            $stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name FROM projects p JOIN users u ON p.owner_id=u.id WHERE p.id=?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            $project['tags']     = $project['tags'] ? explode(',', $project['tags']) : [];
            $project['my_role']  = 'owner';
            $project['progress'] = 0;

            $template = trim($data['template'] ?? '');
            $seedResult = null;
            if ($template && in_array($template, TEMPLATE_IDS, true)) {
                $seedResult = seedProjectTemplate($pdo, $projectId, $userId, $template);
            }

            echo json_encode(['project' => $project, 'seed' => $seedResult]);
            exit;
        }

        // Update project
        if ($action === 'update') {
            $projectId = (int)($data['id'] ?? 0);
            requireRole($pdo, $projectId, $userId, ['owner','admin']);

            $tags = '';
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $tags = implode(',', array_map('trim', array_slice($data['tags'], 0, 10)));
            }

            $sets = [
                'title=?', 'description=?', 'category=?', 'stage=?',
                'tags=?', 'looking_for=?', 'website_url=?', 'github_url=?', 'is_public=?',
            ];
            $params = [
                trim($data['title']       ?? ''),
                trim($data['description'] ?? ''),
                $data['category']    ?? 'other',
                $data['stage']       ?? 'idea',
                $tags,
                $data['looking_for'] ?? null,
                $data['website_url'] ?? null,
                $data['github_url']  ?? null,
                isset($data['is_public']) ? (int)(bool)$data['is_public'] : 1,
            ];
            if (array_key_exists('cover_url', $data)) {
                $sets[] = 'cover_url=?';
                $cover = trim((string)($data['cover_url'] ?? ''));
                $params[] = $cover !== '' ? $cover : null;
            }
            $params[] = $projectId;
            $pdo->prepare('UPDATE projects SET ' . implode(', ', $sets) . ' WHERE id=?')->execute($params);
            echo json_encode(['success' => true]);
            exit;
        }

        // Follow / unfollow
        if ($action === 'follow') {
            $projectId = (int)($data['id'] ?? 0);
            $check = $pdo->prepare("SELECT id FROM project_followers WHERE project_id=? AND user_id=?");
            $check->execute([$projectId, $userId]);
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM project_followers WHERE project_id=? AND user_id=?")->execute([$projectId, $userId]);
                echo json_encode(['following' => false]);
            } else {
                $pdo->prepare("INSERT INTO project_followers (project_id, user_id) VALUES (?,?)")->execute([$projectId, $userId]);
                echo json_encode(['following' => true]);
            }
            exit;
        }

        // Join request
        if ($action === 'join_request') {
            $projectId = (int)($data['id'] ?? 0);
            $message   = trim($data['message'] ?? '');
            $roleId    = (int)($data['role_id'] ?? 0);

            if ($roleId) {
                $role = fetchRole($pdo, $roleId);
                if ((int)$role['project_id'] !== $projectId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Роль не относится к проекту']);
                    exit;
                }
                if ($role['status'] !== 'open') {
                    http_response_code(400);
                    echo json_encode(['error' => 'Эта роль закрыта']);
                    exit;
                }
                $chk = $pdo->prepare("SELECT id FROM project_members WHERE project_id=? AND user_id=?");
                $chk->execute([$projectId, $userId]);
                if ($chk->fetch()) { http_response_code(400); echo json_encode(['error' => 'Вы уже в команде']); exit; }

                $pdo->prepare("
                    INSERT INTO project_role_applications (role_id, project_id, user_id, message, status)
                    VALUES (?,?,?,?, 'pending')
                    ON DUPLICATE KEY UPDATE message=VALUES(message), status='pending', updated_at=CURRENT_TIMESTAMP
                ")->execute([$roleId, $projectId, $userId, $message]);
                echo json_encode(['success' => true, 'type' => 'role']);
                exit;
            }

            // Check not already member
            $chk = $pdo->prepare("SELECT id FROM project_members WHERE project_id=? AND user_id=?");
            $chk->execute([$projectId, $userId]);
            if ($chk->fetch()) { http_response_code(400); echo json_encode(['error' => 'Вы уже в команде']); exit; }

            $pdo->prepare("
                INSERT INTO project_join_requests (project_id, user_id, message)
                VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE message=VALUES(message), status='pending'
            ")->execute([$projectId, $userId, $message]);
            echo json_encode(['success' => true]);
            exit;
        }

        // Accept / reject join request (admin/owner)
        if ($action === 'handle_request') {
            $projectId = (int)($data['project_id'] ?? 0);
            requireRole($pdo, $projectId, $userId, ['owner','admin']);
            $requestUserId = (int)($data['user_id'] ?? 0);
            $decision      = $data['decision'] ?? ''; // 'accept' | 'reject'

            if ($decision === 'accept') {
                $pdo->prepare("UPDATE project_join_requests SET status='accepted' WHERE project_id=? AND user_id=?")->execute([$projectId, $requestUserId]);
                $jrRole = $pdo->prepare("SELECT role_id FROM project_join_requests WHERE project_id=? AND user_id=?");
                $jrRole->execute([$projectId, $requestUserId]);
                $roleIdRow = $jrRole->fetchColumn();
                $spec = 'other';
                if ($roleIdRow) {
                    $rs = $pdo->prepare("SELECT specialization FROM project_roles WHERE id=?");
                    $rs->execute([(int)$roleIdRow]);
                    $spec = normalizeSpec($rs->fetchColumn() ?: 'other') ?: 'other';
                }
                $pdo->prepare("
                    INSERT IGNORE INTO project_members (project_id, user_id, permission_role, specialization_role)
                    VALUES (?,?,'member',?)
                ")->execute([$projectId, $requestUserId, $spec]);
                // Log activity
                try {
                    $newUser = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id=?");
                    $newUser->execute([$requestUserId]);
                    $nu = $newUser->fetch();
                    if ($nu) {
                        $pdo->prepare("INSERT INTO project_activity (project_id, user_id, type, meta) VALUES (?,?,'joined',?)")
                            ->execute([$projectId, $requestUserId, $nu['first_name'].' '.$nu['last_name'].' вступил в проект']);
                    }
                } catch (Throwable $e) {}
            } elseif ($decision === 'reject') {
                $pdo->prepare("UPDATE project_join_requests SET status='rejected' WHERE project_id=? AND user_id=?")->execute([$projectId, $requestUserId]);
            }
            echo json_encode(['success' => true]);
            exit;
        }

        // Change member role
        if ($action === 'change_role') {
            $projectId      = (int)($data['project_id'] ?? 0);
            requireRole($pdo, $projectId, $userId, ['owner','admin']);
            $targetUserId   = (int)($data['user_id'] ?? 0);
            $permRole       = $data['permission_role']     ?? null;
            $specRole       = $data['specialization_role'] ?? null;

            // Нельзя менять роль владельца проекта
            $tgt = $pdo->prepare("SELECT permission_role FROM project_members WHERE project_id=? AND user_id=?");
            $tgt->execute([$projectId, $targetUserId]);
            $tgtRow = $tgt->fetch();
            if ($tgtRow && $tgtRow['permission_role'] === 'owner') {
                http_response_code(403); echo json_encode(['error' => 'Нельзя изменить роль владельца']); exit;
            }
            // Допустимые значения
            $allowedPerms = ['admin','member','viewer'];
            $allowedSpecs = ['product_manager','frontend','backend','designer','ml_engineer','fullstack','analyst','qa','marketing','other'];
            if ($permRole !== null && !in_array($permRole, $allowedPerms, true)) { $permRole = null; }
            if ($specRole !== null && !in_array($specRole, $allowedSpecs, true)) { $specRole = null; }

            if ($permRole) {
                $pdo->prepare("UPDATE project_members SET permission_role=? WHERE project_id=? AND user_id=?")->execute([$permRole, $projectId, $targetUserId]);
            }
            if ($specRole) {
                $pdo->prepare("UPDATE project_members SET specialization_role=? WHERE project_id=? AND user_id=?")->execute([$specRole, $projectId, $targetUserId]);
            }
            echo json_encode(['success' => true]);
            exit;
        }

        // Remove member
        if ($action === 'remove_member') {
            $projectId    = (int)($data['project_id'] ?? 0);
            requireRole($pdo, $projectId, $userId, ['owner','admin']);
            $targetUserId = (int)($data['user_id'] ?? 0);
            // Can't remove owner
            $ownerCheck = $pdo->prepare("SELECT permission_role FROM project_members WHERE project_id=? AND user_id=?");
            $ownerCheck->execute([$projectId, $targetUserId]);
            $mem = $ownerCheck->fetch();
            if ($mem && $mem['permission_role'] === 'owner') { http_response_code(403); echo json_encode(['error' => 'Нельзя удалить владельца']); exit; }
            $pdo->prepare("DELETE FROM project_members WHERE project_id=? AND user_id=?")->execute([$projectId, $targetUserId]);
            echo json_encode(['success' => true]);
            exit;
        }

        http_response_code(400); echo json_encode(['error' => 'Неизвестный action']); exit;
    }

    // ── DELETE ──────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $userId    = verifyToken();
        $projectId = (int)($_GET['id'] ?? 0);
        requireRole($pdo, $projectId, $userId, ['owner']);
        $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$projectId]);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405); echo json_encode(['error' => 'Метод не разрешён']);

} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error' => 'DB: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}

// ── Helpers ─────────────────────────────────────────────────────
function requireRole(PDO $pdo, int $projectId, int $userId, array $roles): void {
    $stmt = $pdo->prepare("SELECT permission_role FROM project_members WHERE project_id=? AND user_id=?");
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch();
    if (!$row || !in_array($row['permission_role'], $roles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Недостаточно прав']);
        exit;
    }
}