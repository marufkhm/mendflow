<?php
// Sprint 9: server-side MIME validation via finfo
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

try {
    $userId = verifyToken();

    $postMax    = parseIniBytes(ini_get('post_max_size'));
    $uploadMax  = parseIniBytes(ini_get('upload_max_filesize'));
    $serverMax  = max($postMax, $uploadMax);
    $contentLen = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if (empty($_FILES) && $contentLen > 0 && $serverMax > 0 && $contentLen > $serverMax) {
        http_response_code(413);
        $limitMb = max(1, (int)round($serverMax / 1024 / 1024));
        echo json_encode(['error' => "Файл слишком большой для сервера (лимит ~{$limitMb} МБ)."]);
        exit;
    }

    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Файл не загружен']);
        exit;
    }

    $allowedMimes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/quicktime',
    ];
    $isVideo  = strpos((string)($_FILES['image']['type'] ?? ''), 'video/') === 0;
    $maxBytes = $isVideo ? 50 * 1024 * 1024 : 25 * 1024 * 1024;

    $check = validateUploadedFile($_FILES['image'], $allowedMimes, $maxBytes);
    if (isset($check['error'])) {
        http_response_code(400);
        echo json_encode(['error' => $check['error']]);
        exit;
    }

    $realMime = normalizeUploadMime($check['mime']);
    $isVideo  = str_starts_with($realMime, 'video/');

    $prefix   = $isVideo ? 'video_' : 'img_';
    $basename = $prefix . $userId . '_' . mfUniqueUploadToken() . '.' . safeExtFromMime($realMime);

    $uploadDir = mfUploadsDir();
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось создать папку uploads/. Проверьте права на сервере.']);
        exit;
    }
    if (!is_writable($uploadDir)) {
        http_response_code(500);
        echo json_encode(['error' => 'Папка uploads/ недоступна для записи. Установите права 755 или 775.']);
        exit;
    }

    $tmpPath = $uploadDir . 'tmp_' . mfUniqueUploadToken();
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $tmpPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка сохранения. Проверь права папки uploads/ (755)']);
        exit;
    }

    if ($isVideo) {
        $filename = $basename;
        try {
            mfPersistUploadFile($tmpPath, $uploadDir . $filename);
        } catch (Throwable $e) {
            @unlink($tmpPath);
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка сохранения видео']);
            exit;
        }
    } else {
        try {
            $saved    = saveUploadedImageOptimized($tmpPath, $uploadDir, $basename, $realMime);
            $filename = $saved['filename'];
        } catch (Throwable $e) {
            @unlink($tmpPath);
            error_log('upload.php image processing: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка обработки изображения. Попробуйте JPG или PNG до 10 МБ.']);
            exit;
        }
    }

    $finalPath = $uploadDir . $filename;
    if (!mfVerifyUploadFile($finalPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Файл не найден после загрузки. Проверьте права папки uploads/.']);
        exit;
    }

    echo json_encode([
        'success'  => true,
        'url'      => mfUploadPublicUrl($filename),
        'path'     => '/uploads/' . $filename,
        'filename' => $filename,
        'bytes'    => (int)filesize($finalPath),
        'kind'     => $isVideo ? 'video' : 'image',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    error_log('upload.php fatal: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
