<?php
// Sprint 9: server-side MIME validation via finfo
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $userId = verifyToken();

    // Ensure cover_image column exists
    try {
        $exists = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
            AND COLUMN_NAME = 'cover_image' LIMIT 1")->fetchColumn();
        if (!$exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN cover_image VARCHAR(500) NULL");
        }
    } catch (Throwable $e) {}

    $isAvatar = isset($_FILES['avatar']);
    $isCover  = isset($_FILES['cover']);

    if (!$isAvatar && !$isCover) {
        http_response_code(400);
        echo json_encode(['error' => 'Файл не загружен (ожидается поле avatar или cover)']);
        exit;
    }

    $file  = $isAvatar ? $_FILES['avatar'] : $_FILES['cover'];
    $field = $isAvatar ? 'avatar' : 'cover_image';

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxBytes     = 10 * 1024 * 1024; // 10 МБ

    // ── Sprint 9: server-side MIME через finfo ────────────────
    $check = validateUploadedFile($file, $allowedMimes, $maxBytes);
    if (isset($check['error'])) {
        http_response_code(400);
        echo json_encode(['error' => $check['error']]);
        exit;
    }

    $prefix   = $isAvatar ? 'avatar' : 'cover';
    $basename = $prefix . '_' . $userId . '_' . uniqid('', true) . '.' . safeExtFromMime($check['mime']);

    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $tmpPath = $uploadDir . 'tmp_' . uniqid('', true);
    if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось сохранить файл. Проверь права папки uploads/']);
        exit;
    }

    try {
        $saved    = saveUploadedImageOptimized($tmpPath, $uploadDir, $basename, $check['mime']);
        $filename = $saved['filename'];
    } catch (Throwable $e) {
        @unlink($tmpPath);
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка обработки изображения']);
        exit;
    }

    $url = mfUploadPublicUrl($filename);

    $pdo->prepare("UPDATE users SET `{$field}` = ? WHERE id = ?")->execute([$url, $userId]);

    echo json_encode(['success' => true, $field => $url, 'url' => $url]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
