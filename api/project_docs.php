<?php
/**
 * MENDFLOW — project_docs.php
 * Sprint 3: Docs / Knowledge Base API
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once __DIR__ . '/project_templates.php';

try {
    ensureSprint14Schema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET ───────────────────────────────────────────────────
    if ($method === 'GET') {
        $userId    = verifyToken();
        $projectId = (int)($_GET['project_id'] ?? 0);
        $docId     = (int)($_GET['id']         ?? 0);
        $search    = trim($_GET['q']            ?? '');

        if (!$projectId) { http_response_code(400); echo json_encode(['error'=>'project_id required']); exit; }
        requireAccess($pdo, $projectId, $userId);

        // Single doc
        if ($docId) {
            $stmt = $pdo->prepare("
                SELECT d.*, u.first_name, u.last_name
                FROM project_docs d JOIN users u ON d.author_id = u.id
                WHERE d.id = ? AND d.project_id = ?
            ");
            $stmt->execute([$docId, $projectId]);
            $doc = $stmt->fetch();
            if (!$doc) { http_response_code(404); echo json_encode(['error'=>'Документ не найден']); exit; }
            echo json_encode(['doc' => $doc]);
            exit;
        }

        // Search
        if ($search) {
            $like = '%' . $search . '%';
            $stmt = $pdo->prepare("
                SELECT d.id, d.title, d.parent_id, d.created_at, d.updated_at,
                    SUBSTRING(d.content, 1, 200) AS excerpt
                FROM project_docs d
                WHERE d.project_id = ? AND (d.title LIKE ? OR d.content LIKE ?)
                ORDER BY d.updated_at DESC LIMIT 20
            ");
            $stmt->execute([$projectId, $like, $like]);
            $results = $stmt->fetchAll();
            foreach ($results as &$r) {
                $r['time_ago'] = timeAgo($r['updated_at'] ?? $r['created_at']);
            }
            echo json_encode(['results' => $results]);
            exit;
        }

        // Vault index (Obsidian: links, graph, backlinks)
        if (!empty($_GET['vault'])) {
            $stmt = $pdo->prepare("
                SELECT d.id, d.title, d.kind, d.parent_id, d.position, d.content, d.updated_at,
                    u.first_name, u.last_name
                FROM project_docs d JOIN users u ON d.author_id = u.id
                WHERE d.project_id = ?
                ORDER BY d.parent_id ASC, d.position ASC, d.created_at ASC
            ");
            $stmt->execute([$projectId]);
            $docs = $stmt->fetchAll();
            foreach ($docs as &$d) { $d['time_ago'] = timeAgo($d['updated_at']); }
            echo json_encode(['docs' => $docs]);
            exit;
        }

        // All docs tree (no content — just metadata for sidebar)
        $kindFilter = $_GET['kind'] ?? '';
        $whereKind = '';
        $params = [$projectId];
        if ($kindFilter === 'doc' || $kindFilter === 'wiki') {
            $whereKind = " AND d.kind = ?";
            $params[] = $kindFilter;
        }
        $stmt = $pdo->prepare("
            SELECT d.id, d.title, d.kind, d.parent_id, d.position, d.updated_at,
                u.first_name, u.last_name
            FROM project_docs d JOIN users u ON d.author_id = u.id
            WHERE d.project_id = ?$whereKind
            ORDER BY d.parent_id ASC, d.position ASC, d.created_at ASC
        ");
        $stmt->execute($params);
        $docs = $stmt->fetchAll();
        foreach ($docs as &$d) { $d['time_ago'] = timeAgo($d['updated_at']); }
        echo json_encode(['docs' => $docs]);
        exit;
    }

    // ── POST ──────────────────────────────────────────────────
    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? 'create';

        // Create doc
        if ($action === 'create') {
            $projectId = (int)($data['project_id'] ?? 0);
            if (!$projectId) { http_response_code(400); echo json_encode(['error'=>'project_id required']); exit; }
            requireMember($pdo, $projectId, $userId);

            $parentId = ($data['parent_id'] ?? null) ? (int)$data['parent_id'] : null;
            $title    = trim($data['title'] ?? '') ?: 'Без названия';
            $kind     = ($data['kind'] ?? 'doc') === 'wiki' ? 'wiki' : 'doc';

            // Get next position
            $posStmt = $pdo->prepare("SELECT COALESCE(MAX(position),0)+1 FROM project_docs WHERE project_id=? AND parent_id" . ($parentId ? "=?" : " IS NULL"));
            $parentId ? $posStmt->execute([$projectId, $parentId]) : $posStmt->execute([$projectId]);
            $pos = (int)$posStmt->fetchColumn();

            $pdo->prepare("
                INSERT INTO project_docs (project_id, author_id, parent_id, title, kind, content, position)
                VALUES (?,?,?,?,?,?,?)
            ")->execute([$projectId, $userId, $parentId, $title, $kind, '', $pos]);
            $docId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("SELECT d.*, u.first_name, u.last_name FROM project_docs d JOIN users u ON d.author_id=u.id WHERE d.id=?");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch();
            $doc['time_ago'] = 'только что';
            echo json_encode(['doc' => $doc]);
            exit;
        }

        // Autosave
        if ($action === 'save') {
            $docId = (int)($data['id'] ?? 0);
            if (!$docId) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }

            $stmt = $pdo->prepare("SELECT project_id FROM project_docs WHERE id=?");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch();
            if (!$doc) { http_response_code(404); echo json_encode(['error'=>'Не найден']); exit; }
            requireMember($pdo, $doc['project_id'], $userId);

            $pdo->prepare("UPDATE project_docs SET title=?, content=? WHERE id=?")->execute([
                trim($data['title'] ?? '') ?: 'Без названия',
                $data['content'] ?? '',
                $docId,
            ]);
            echo json_encode(['success'=>true, 'saved_at'=>date('H:i:s')]);
            exit;
        }

        // Reorder (drag-drop in sidebar)
        if ($action === 'reorder') {
            $docId    = (int)($data['id']        ?? 0);
            $parentId = ($data['parent_id'] ?? null) !== null ? (int)$data['parent_id'] : null;
            $position = (int)($data['position']   ?? 0);

            $stmt = $pdo->prepare("SELECT project_id FROM project_docs WHERE id=?");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch();
            if (!$doc) { http_response_code(404); echo json_encode(['error'=>'Не найден']); exit; }
            requireMember($pdo, $doc['project_id'], $userId);

            $pdo->prepare("UPDATE project_docs SET parent_id=?, position=? WHERE id=?")
                ->execute([$parentId, $position, $docId]);
            echo json_encode(['success'=>true]);
            exit;
        }

        http_response_code(400); echo json_encode(['error'=>'Неизвестный action']); exit;
    }

    // ── DELETE ────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $userId = verifyToken();
        $docId  = (int)($_GET['id'] ?? 0);
        if (!$docId) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }

        $stmt = $pdo->prepare("SELECT project_id, author_id FROM project_docs WHERE id=?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch();
        if (!$doc) { http_response_code(404); echo json_encode(['error'=>'Не найден']); exit; }

        if ((int)$doc['author_id'] !== $userId) {
            requireRole($pdo, $doc['project_id'], $userId, ['owner','admin']);
        }

        // Delete doc and its children recursively
        deleteDocRecursive($pdo, $docId);
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Метод не разрешён']);

} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>'DB: '.$e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
}

// ── Helpers ──────────────────────────────────────────────────
function deleteDocRecursive(PDO $pdo, int $docId): void {
    $children = $pdo->prepare("SELECT id FROM project_docs WHERE parent_id=?");
    $children->execute([$docId]);
    foreach ($children->fetchAll() as $child) {
        deleteDocRecursive($pdo, $child['id']);
    }
    $pdo->prepare("DELETE FROM project_docs WHERE id=?")->execute([$docId]);
}

function requireAccess(PDO $pdo, int $projectId, int $userId): void {
    $stmt = $pdo->prepare("SELECT is_public FROM projects WHERE id=?");
    $stmt->execute([$projectId]);
    $p = $stmt->fetch();
    if (!$p) { http_response_code(404); echo json_encode(['error'=>'Проект не найден']); exit; }
    if (!$p['is_public']) requireMember($pdo, $projectId, $userId);
}

function requireMember(PDO $pdo, int $projectId, int $userId): void {
    $stmt = $pdo->prepare("SELECT id FROM project_members WHERE project_id=? AND user_id=?");
    $stmt->execute([$projectId, $userId]);
    if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['error'=>'Только участники команды']); exit; }
}

function requireRole(PDO $pdo, int $projectId, int $userId, array $roles): void {
    $stmt = $pdo->prepare("SELECT permission_role FROM project_members WHERE project_id=? AND user_id=?");
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch();
    if (!$row || !in_array($row['permission_role'], $roles)) {
        http_response_code(403); echo json_encode(['error'=>'Нет прав']); exit;
    }
}
