<?php
/**
 * project_resources.php — ссылки команды (GitHub, Figma, …)
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once __DIR__ . '/project_templates.php';

const RESOURCE_KINDS = ['github', 'figma', 'notion', 'drive', 'website', 'training', 'other'];

function normalizeResourceKind(?string $kind): string
{
    $kind = strtolower(trim($kind ?? 'other'));
    return in_array($kind, RESOURCE_KINDS, true) ? $kind : 'other';
}

function requireResourceAccess(PDO $pdo, int $projectId, int $userId): void
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

function requireResourceEdit(PDO $pdo, int $projectId, int $userId): void
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

try {
    ensureSprint14Schema($pdo);
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
            requireResourceAccess($pdo, $projectId, $userId);
        } else {
            $pub = $pdo->prepare('SELECT is_public FROM projects WHERE id=?');
            $pub->execute([$projectId]);
            if (!$pub->fetchColumn()) {
                http_response_code(403);
                echo json_encode(['error' => 'Нет доступа']);
                exit;
            }
        }
        echo json_encode(['resources' => fetchProjectResources($pdo, $projectId)]);
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
            requireResourceEdit($pdo, $projectId, $userId);
            $pos = (int)$pdo->query('SELECT COALESCE(MAX(position),0)+1 FROM project_resources WHERE project_id=' . (int)$projectId)->fetchColumn();
            $pdo->prepare('
                INSERT INTO project_resources (project_id, kind, title, url, position)
                VALUES (?,?,?,?,?)
            ')->execute([
                $projectId,
                normalizeResourceKind($data['kind'] ?? 'other'),
                mb_substr($title, 0, 120),
                mb_substr(trim($data['url'] ?? ''), 0, 500),
                $pos,
            ]);
            $id = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT * FROM project_resources WHERE id=?');
            $stmt->execute([$id]);
            echo json_encode(['resource' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($data['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM project_resources WHERE id=?');
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$res) {
                http_response_code(404);
                echo json_encode(['error' => 'Не найден']);
                exit;
            }
            requireResourceEdit($pdo, (int)$res['project_id'], $userId);
            $pdo->prepare('UPDATE project_resources SET kind=?, title=?, url=? WHERE id=?')->execute([
                normalizeResourceKind($data['kind'] ?? $res['kind']),
                mb_substr(trim($data['title'] ?? $res['title']), 0, 120),
                mb_substr(trim($data['url'] ?? $res['url']), 0, 500),
                $id,
            ]);
            $stmt->execute([$id]);
            echo json_encode(['resource' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($data['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT project_id FROM project_resources WHERE id=?');
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$res) {
                http_response_code(404);
                echo json_encode(['error' => 'Не найден']);
                exit;
            }
            requireResourceEdit($pdo, (int)$res['project_id'], $userId);
            $pdo->prepare('DELETE FROM project_resources WHERE id=?')->execute([$id]);
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
