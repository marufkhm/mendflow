<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'db.php';
require_once __DIR__ . '/feed_ranking.php';
$currentUserId = verifyToken();
global $pdo;

// ── Авто-создание таблицы messages если нет ──────────────────────
$pdo->exec("
  CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    from_id     INT NOT NULL,
    to_id       INT NOT NULL,
    content     TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from (from_id),
    INDEX idx_to   (to_id),
    INDEX idx_conv (from_id, to_id),
    INDEX idx_time (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$method = $_SERVER['REQUEST_METHOD'];

// ═══════════════════════════════════════════════════════════════════
// GET /messages.php?conversations=1  — список диалогов
// ═══════════════════════════════════════════════════════════════════
if ($method === 'GET' && isset($_GET['conversations'])) {

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.first_name,
            u.last_name,
            u.avatar,
            m.content   AS last_message,
            m.created_at AS last_time,
            m.is_read,
            m.from_id   AS last_from_id,
            (
                SELECT COUNT(*) FROM messages
                WHERE to_id = :me AND from_id = u.id AND is_read = 0
            ) AS unread_count
        FROM users u
        JOIN messages m ON m.id = (
            SELECT id FROM messages
            WHERE (from_id = u.id AND to_id = :me2)
               OR (from_id = :me3 AND to_id = u.id)
            ORDER BY created_at DESC
            LIMIT 1
        )
        WHERE u.id != :me4
        ORDER BY m.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([
        ':me'  => $currentUserId,
        ':me2' => $currentUserId,
        ':me3' => $currentUserId,
        ':me4' => $currentUserId,
    ]);
    $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматируем
    foreach ($convs as &$c) {
        $c['id']           = (int)$c['id'];
        $c['unread_count'] = (int)$c['unread_count'];
        $c['last_time']    = utcDate($c['last_time']);
    }

    echo json_encode(['conversations' => $convs]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// GET /messages.php?with=UID&limit=50  — история переписки
// ═══════════════════════════════════════════════════════════════════
if ($method === 'GET' && isset($_GET['with'])) {

    $withId = (int)$_GET['with'];
    $limit  = min((int)($_GET['limit'] ?? 50), 200);
    $before = isset($_GET['before']) ? (int)$_GET['before'] : null;

    if (!$withId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing with']);
        exit;
    }

    // Помечаем входящие как прочитанные
    $pdo->prepare("
        UPDATE messages SET is_read = 1
        WHERE from_id = ? AND to_id = ? AND is_read = 0
    ")->execute([$withId, $currentUserId]);

    $params = [$currentUserId, $withId, $withId, $currentUserId];
    $beforeSql = '';
    if ($before) {
        $beforeSql = 'AND m.id < ?';
        $params[] = $before;
    }
    $params[] = $limit;

    $stmt = $pdo->prepare("
        SELECT
            m.id, m.from_id, m.to_id, m.content, m.is_read, m.created_at,
            u.first_name, u.last_name, u.avatar
        FROM messages m
        JOIN users u ON u.id = m.from_id
        WHERE ((m.from_id = ? AND m.to_id = ?)
            OR (m.from_id = ? AND m.to_id = ?))
        $beforeSql
        ORDER BY m.created_at ASC
        LIMIT ?
    ");
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as &$msg) {
        $msg['id']      = (int)$msg['id'];
        $msg['from_id'] = (int)$msg['from_id'];
        $msg['to_id']   = (int)$msg['to_id'];
        $msg['is_read'] = (int)$msg['is_read'];
        $msg['created_at'] = utcDate($msg['created_at']);
    }

    echo json_encode(['messages' => $messages]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// POST /messages.php  — отправить сообщение {to_id, content}
// ═══════════════════════════════════════════════════════════════════
if ($method === 'POST') {

    $data    = json_decode(file_get_contents('php://input'), true);
    $toId    = (int)($data['to_id']   ?? 0);
    $content = trim($data['content']  ?? '');

    if (!$toId || $content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'to_id и content обязательны']);
        exit;
    }
    if ($toId === $currentUserId) {
        http_response_code(400);
        echo json_encode(['error' => 'Нельзя писать самому себе']);
        exit;
    }
    if (mb_strlen($content) > 5000) {
        http_response_code(400);
        echo json_encode(['error' => 'Сообщение слишком длинное (макс. 5000 символов)']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO messages (from_id, to_id, content, created_at)
        VALUES (?, ?, ?, UTC_TIMESTAMP())
    ");
    $stmt->execute([$currentUserId, $toId, $content]);
    $newId = (int)$pdo->lastInsertId();

    // DM-шер поста в личку — сильный сигнал для рекомендаций
    if (preg_match('/%%POST%%(.+?)%%POST_END%%/s', $content, $m)) {
        try {
            $payload = json_decode($m[1], true);
            $sharedPostId = (int)($payload['id'] ?? 0);
            if ($sharedPostId) {
                frRecordDmShare($pdo, $sharedPostId, $currentUserId, $toId);
            }
        } catch (Throwable $e) {}
    }

    // Возвращаем сохранённое сообщение
    $row = $pdo->prepare("
        SELECT m.id, m.from_id, m.to_id, m.content, m.is_read, m.created_at,
               u.first_name, u.last_name, u.avatar
        FROM messages m
        JOIN users u ON u.id = m.from_id
        WHERE m.id = ?
    ");
    $row->execute([$newId]);
    $message = $row->fetch(PDO::FETCH_ASSOC);
    $message['id']      = (int)$message['id'];
    $message['from_id'] = (int)$message['from_id'];
    $message['to_id']   = (int)$message['to_id'];
    $message['created_at'] = utcDate($message['created_at']);

    require_once __DIR__ . '/push_helper.php';
    try {
        $fromRow = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1');
        $fromRow->execute([$currentUserId]);
        $fromUser = $fromRow->fetch(PDO::FETCH_ASSOC) ?: [];
        $fromName = trim(($fromUser['first_name'] ?? '') . ' ' . ($fromUser['last_name'] ?? ''));
        $preview  = mb_substr($content, 0, 120);
        mfSendWebPush(
            $pdo,
            $toId,
            'Новое сообщение',
            ($fromName !== '' ? $fromName . ': ' : '') . $preview,
            './'
        );
    } catch (Throwable $e) {}

    echo json_encode(['success' => true, 'message' => $message]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Метод не поддерживается']);
