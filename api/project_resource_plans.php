<?php
/**
 * project_resource_plans.php — планирование ресурсов проекта
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

const PLAN_CATEGORIES = ['people', 'budget', 'tools', 'training', 'infrastructure'];
const PLAN_STATUSES   = ['planned', 'partial', 'ready'];
const PLAN_UNITS      = ['hrs', 'kzt', 'usd', 'pcs', 'gb', 'pct'];

function normalizePlanCategory(?string $cat): string
{
    $cat = strtolower(trim($cat ?? 'tools'));
    return in_array($cat, PLAN_CATEGORIES, true) ? $cat : 'tools';
}

function normalizePlanStatus(?string $status): string
{
    $status = strtolower(trim($status ?? 'planned'));
    return in_array($status, PLAN_STATUSES, true) ? $status : 'planned';
}

function normalizePlanUnit(?string $unit): string
{
    $unit = strtolower(trim($unit ?? 'pcs'));
    return in_array($unit, PLAN_UNITS, true) ? $unit : 'pcs';
}

function ensureResourcePlansSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_resource_plans (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        project_id       INT NOT NULL,
        category         ENUM('people','budget','tools','training','infrastructure') NOT NULL DEFAULT 'tools',
        title            VARCHAR(120) NOT NULL,
        planned_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
        allocated_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        unit             VARCHAR(20) NOT NULL DEFAULT 'pcs',
        status           ENUM('planned','partial','ready') NOT NULL DEFAULT 'planned',
        notes            VARCHAR(500) NOT NULL DEFAULT '',
        due_date         DATE NULL DEFAULT NULL,
        position         INT NOT NULL DEFAULT 0,
        created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_prp_project (project_id),
        KEY idx_prp_category (project_id, category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function fetchProjectResourcePlans(PDO $pdo, int $projectId): array
{
    ensureResourcePlansSchema($pdo);
    $stmt = $pdo->prepare('
        SELECT * FROM project_resource_plans
        WHERE project_id = ?
        ORDER BY position ASC, id ASC
    ');
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['id']               = (int)$r['id'];
        $r['project_id']       = (int)$r['project_id'];
        $r['planned_amount']   = (float)$r['planned_amount'];
        $r['allocated_amount'] = (float)$r['allocated_amount'];
        $r['position']         = (int)$r['position'];
    }
    unset($r);
    return $rows;
}

function syncPlanStatus(float $planned, float $allocated): string
{
    if ($planned <= 0) {
        return $allocated > 0 ? 'partial' : 'planned';
    }
    if ($allocated >= $planned) {
        return 'ready';
    }
    if ($allocated > 0) {
        return 'partial';
    }
    return 'planned';
}

function requirePlanAccess(PDO $pdo, int $projectId, int $userId): void
{
    $stmt = $pdo->prepare('SELECT is_public FROM projects WHERE id=?');
    $stmt->execute([$projectId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
        http_response_code(404);
        echo json_encode(['error' => 'Проект не найден']);
        exit;
    }
    if (!$p['is_public']) {
        $m = $pdo->prepare('SELECT id FROM project_members WHERE project_id=? AND user_id=?');
        $m->execute([$projectId, $userId]);
        if (!$m->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Нет доступа']);
            exit;
        }
    }
}

function requirePlanEdit(PDO $pdo, int $projectId, int $userId): void
{
    $stmt = $pdo->prepare('SELECT permission_role FROM project_members WHERE project_id=? AND user_id=?');
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !in_array($row['permission_role'], ['owner', 'admin'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Недостаточно прав']);
        exit;
    }
}

if (mfIsDirectScript(__FILE__)) {
try {
    ensureResourcePlansSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $userId    = verifyTokenSoft();
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) {
            http_response_code(400);
            echo json_encode(['error' => 'project_id required']);
            exit;
        }
        if ($userId) {
            requirePlanAccess($pdo, $projectId, $userId);
        } else {
            $pub = $pdo->prepare('SELECT is_public FROM projects WHERE id=?');
            $pub->execute([$projectId]);
            if (!$pub->fetchColumn()) {
                http_response_code(403);
                echo json_encode(['error' => 'Нет доступа']);
                exit;
            }
        }
        $plans = fetchProjectResourcePlans($pdo, $projectId);
        $summary = ['people_hrs' => 0, 'budget_kzt' => 0, 'tools' => 0, 'training_hrs' => 0, 'ready' => 0];
        foreach ($plans as $pl) {
            if ($pl['status'] === 'ready') {
                $summary['ready']++;
            }
            if ($pl['category'] === 'people' && $pl['unit'] === 'hrs') {
                $summary['people_hrs'] += $pl['planned_amount'];
            } elseif ($pl['category'] === 'budget' && $pl['unit'] === 'kzt') {
                $summary['budget_kzt'] += $pl['planned_amount'];
            } elseif ($pl['category'] === 'tools') {
                $summary['tools']++;
            } elseif ($pl['category'] === 'training' && $pl['unit'] === 'hrs') {
                $summary['training_hrs'] += $pl['planned_amount'];
            }
        }
        echo json_encode(['plans' => $plans, 'summary' => $summary], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? 'create';

        if ($action === 'create') {
            $projectId = (int)($data['project_id'] ?? 0);
            $title     = trim($data['title'] ?? '');
            if (!$projectId || !$title) {
                http_response_code(400);
                echo json_encode(['error' => 'project_id и title обязательны']);
                exit;
            }
            requirePlanEdit($pdo, $projectId, $userId);
            $planned   = max(0, (float)($data['planned_amount'] ?? 0));
            $allocated = max(0, (float)($data['allocated_amount'] ?? 0));
            $status    = normalizePlanStatus($data['status'] ?? syncPlanStatus($planned, $allocated));
            $pos = (int)$pdo->query('SELECT COALESCE(MAX(position),0)+1 FROM project_resource_plans WHERE project_id=' . (int)$projectId)->fetchColumn();
            $pdo->prepare('
                INSERT INTO project_resource_plans
                    (project_id, category, title, planned_amount, allocated_amount, unit, status, notes, due_date, position)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ')->execute([
                $projectId,
                normalizePlanCategory($data['category'] ?? 'tools'),
                mb_substr($title, 0, 120),
                $planned,
                $allocated,
                normalizePlanUnit($data['unit'] ?? 'pcs'),
                $status,
                mb_substr(trim($data['notes'] ?? ''), 0, 500),
                ($data['due_date'] ?? '') !== '' ? $data['due_date'] : null,
                $pos,
            ]);
            $id = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT * FROM project_resource_plans WHERE id=?');
            $stmt->execute([$id]);
            echo json_encode(['plan' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($data['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM project_resource_plans WHERE id=?');
            $stmt->execute([$id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) {
                http_response_code(404);
                echo json_encode(['error' => 'Не найден']);
                exit;
            }
            requirePlanEdit($pdo, (int)$plan['project_id'], $userId);
            $planned   = max(0, (float)($data['planned_amount'] ?? $plan['planned_amount']));
            $allocated = max(0, (float)($data['allocated_amount'] ?? $plan['allocated_amount']));
            $status    = normalizePlanStatus($data['status'] ?? syncPlanStatus($planned, $allocated));
            $pdo->prepare('
                UPDATE project_resource_plans
                SET category=?, title=?, planned_amount=?, allocated_amount=?, unit=?, status=?, notes=?, due_date=?
                WHERE id=?
            ')->execute([
                normalizePlanCategory($data['category'] ?? $plan['category']),
                mb_substr(trim($data['title'] ?? $plan['title']), 0, 120),
                $planned,
                $allocated,
                normalizePlanUnit($data['unit'] ?? $plan['unit']),
                $status,
                mb_substr(trim($data['notes'] ?? $plan['notes']), 0, 500),
                array_key_exists('due_date', $data)
                    ? (($data['due_date'] ?? '') !== '' ? $data['due_date'] : null)
                    : $plan['due_date'],
                $id,
            ]);
            $stmt->execute([$id]);
            echo json_encode(['plan' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($data['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT project_id FROM project_resource_plans WHERE id=?');
            $stmt->execute([$id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) {
                http_response_code(404);
                echo json_encode(['error' => 'Не найден']);
                exit;
            }
            requirePlanEdit($pdo, (int)$plan['project_id'], $userId);
            $pdo->prepare('DELETE FROM project_resource_plans WHERE id=?')->execute([$id]);
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
