<?php
/**
 * config.php — загрузка .env и вспомогательные функции конфигурации.
 * Подключается ПЕРВЫМ из db.php, до любого другого кода.
 */

// Путь к .env — на уровень выше папки api/
$_mf_env = dirname(__DIR__) . '/.env';

if (is_readable($_mf_env)) {
    foreach (file($_mf_env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        // Убираем кавычки
        if (strlen($v) >= 2 && (
            ($v[0] === '"'  && $v[-1] === '"') ||
            ($v[0] === "'"  && $v[-1] === "'")
        )) {
            $v = substr($v, 1, -1);
        }
        // Не перезаписываем переменные, уже заданные окружением сервера
        if (getenv($k) === false) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
        }
    }
}
unset($_mf_env);

/**
 * Читает переменную окружения. Если не найдена — возвращает $default.
 */
function env(string $key, $default = null) {
    $v = $_ENV[$key] ?? getenv($key);
    return ($v !== false && $v !== null && $v !== '') ? $v : $default;
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

function parseIniBytes($value): int
{
    $value = trim((string)$value);
    if ($value === '') {
        return 0;
    }
    $num = (int)$value;
    $last = strtolower(substr($value, -1));
    switch ($last) {
        case 'g': return $num * 1024 * 1024 * 1024;
        case 'm': return $num * 1024 * 1024;
        case 'k': return $num * 1024;
        default:  return (int)$value;
    }
}

function normalizeUploadMime(?string $mime): string
{
    $mime = strtolower(trim((string)$mime));
    $map = [
        'image/pjpeg' => 'image/jpeg',
        'image/x-png' => 'image/png',
        'image/x-jpeg' => 'image/jpeg',
    ];
    return $map[$mime] ?? $mime;
}

function mfUniqueUploadToken(): string
{
    return str_replace('.', '_', uniqid('', true));
}

/**
 * Абсолютный путь к папке uploads/ (можно переопределить через UPLOADS_DIR в .env).
 */
function mfUploadsDir(): string
{
    $custom = trim((string)env('UPLOADS_DIR', ''));
    if ($custom !== '') {
        $custom = rtrim(str_replace('\\', '/', $custom), '/');
        if (is_dir($custom) && is_writable($custom)) {
            return $custom . '/';
        }
    }
    return dirname(__DIR__) . '/uploads/';
}

/**
 * Проверяет, что файл реально записан на диск.
 */
function mfVerifyUploadFile(string $path): bool
{
    clearstatcache(true, $path);
    return is_file($path) && is_readable($path) && filesize($path) > 0;
}

/**
 * Сохраняет tmp-файл в uploads/ и проверяет запись.
 *
 * @throws RuntimeException
 */
function mfPersistUploadFile(string $tmpPath, string $destPath): void
{
    if (!is_file($tmpPath) || filesize($tmpPath) <= 0) {
        throw new RuntimeException('Временный файл пуст или отсутствует');
    }

    if (@rename($tmpPath, $destPath)) {
        if (mfVerifyUploadFile($destPath)) {
            return;
        }
        @unlink($destPath);
        throw new RuntimeException('Файл не сохранился после rename');
    }

    if (!@copy($tmpPath, $destPath)) {
        throw new RuntimeException('Не удалось скопировать файл в uploads/');
    }
    @unlink($tmpPath);

    if (!mfVerifyUploadFile($destPath)) {
        @unlink($destPath);
        throw new RuntimeException('Файл не найден после копирования в uploads/');
    }
}

/**
 * Sprint 9: Server-side MIME-валидация загружаемого файла через finfo.
 * Не доверяем $_FILES['type'] — он присылается клиентом и может быть подделан.
 *
 * @param array  $file         Элемент $_FILES
 * @param array  $allowedMimes Допустимые MIME-типы
 * @param int    $maxBytes     Максимальный размер в байтах
 * @return array ['ok' => true] или ['error' => '...']
 */
function validateUploadedFile(array $file, array $allowedMimes, int $maxBytes): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'Файл превышает лимит сервера (upload_max_filesize = ' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_FORM_SIZE  => 'Файл превышает допустимый размер формы.',
            UPLOAD_ERR_PARTIAL    => 'Файл загружен частично, попробуй ещё раз.',
            UPLOAD_ERR_NO_FILE    => 'Файл не выбран.',
            UPLOAD_ERR_NO_TMP_DIR => 'На сервере не настроена временная папка.',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
            UPLOAD_ERR_EXTENSION  => 'Загрузка остановлена расширением PHP.',
        ];
        return ['error' => $map[$file['error']] ?? 'Ошибка загрузки (код ' . (int)$file['error'] . ').'];
    }

    if ($file['size'] > $maxBytes) {
        $mb = round($maxBytes / 1024 / 1024);
        return ['error' => "Файл слишком большой. Максимум {$mb} МБ."];
    }

    // Определяем MIME по содержимому файла (не по заголовку браузера)
    $realMime = null;
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($fi, $file['tmp_name']);
        finfo_close($fi);
    } elseif (function_exists('mime_content_type')) {
        $realMime = mime_content_type($file['tmp_name']);
    } else {
        // Fallback: доверяем клиентскому типу только если расширение совпадает
        $realMime = $file['type'];
    }

    $realMime = normalizeUploadMime($realMime);

    if (!in_array($realMime, $allowedMimes, true)) {
        return ['error' => "Недопустимый тип файла ({$realMime}). Разрешены: " . implode(', ', $allowedMimes)];
    }

    return ['ok' => true, 'mime' => $realMime];
}

/**
 * Безопасное расширение файла на основе реального MIME-типа.
 */
function safeExtFromMime(string $mime): string {
    return [
        'image/jpeg'      => 'jpg',
        'image/jpg'       => 'jpg',
        'image/png'       => 'png',
        'image/gif'       => 'gif',
        'image/webp'      => 'webp',
        'video/mp4'       => 'mp4',
        'video/webm'      => 'webm',
        'video/quicktime' => 'mov',
    ][$mime] ?? 'bin';
}

/**
 * Sprint 19: конвертация JPEG/PNG/GIF в WebP (GD).
 */
function convertImageToWebp(string $srcPath, string $destPath, string $mime, int $quality = 85): bool {
    if (!function_exists('imagewebp') || !is_file($srcPath)) {
        return false;
    }

    $mime = normalizeUploadMime($mime);
    $img = null;
    try {
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                if (!function_exists('imagecreatefromjpeg')) {
                    return false;
                }
                $img = @imagecreatefromjpeg($srcPath);
                break;
            case 'image/png':
                if (!function_exists('imagecreatefrompng')) {
                    return false;
                }
                $img = @imagecreatefrompng($srcPath);
                break;
            case 'image/gif':
                if (!function_exists('imagecreatefromgif')) {
                    return false;
                }
                $img = @imagecreatefromgif($srcPath);
                break;
            default:
                return false;
        }
    } catch (Throwable $e) {
        return false;
    }

    if (!$img) {
        return false;
    }

    if ($mime === 'image/png' || $mime === 'image/gif') {
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($img);
        }
        @imagealphablending($img, true);
        @imagesavealpha($img, true);
    }

    $ok = @imagewebp($img, $destPath, max(1, min(100, $quality)));
    @imagedestroy($img);

    return $ok && is_file($destPath) && filesize($destPath) > 0;
}

/**
 * Сохраняет загруженное изображение; по возможности — в WebP.
 *
 * @return array{filename:string, ext:string, mime:string}
 */
function saveUploadedImageOptimized(string $tmpPath, string $uploadDir, string $basename, string $mime, int $quality = 85): array {
    $mime = normalizeUploadMime($mime);
    $useWebp = env('MF_USE_WEBP', '0') === '1';

    if ($useWebp && str_starts_with($mime, 'image/') && $mime !== 'image/webp') {
        $webpName = preg_replace('/\.[^.]+$/', '', $basename) . '.webp';
        $webpPath = $uploadDir . $webpName;
        if (convertImageToWebp($tmpPath, $webpPath, $mime, $quality) && mfVerifyUploadFile($webpPath)) {
            @unlink($tmpPath);
            return ['filename' => $webpName, 'ext' => 'webp', 'mime' => 'image/webp'];
        }
    }

    $ext      = safeExtFromMime($mime);
    $filename = preg_replace('/\.[^.]+$/', '', $basename) . '.' . $ext;
    $dest     = $uploadDir . $filename;

    mfPersistUploadFile($tmpPath, $dest);

    return ['filename' => $filename, 'ext' => $ext, 'mime' => $mime];
}

/**
 * Схема текущего запроса (учитывает прокси InfinityFree / Cloudflare).
 */
function mfRequestScheme(): string
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return 'https';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($proto === 'https') {
            return 'https';
        }
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return 'https';
    }
    $appUrl = rtrim((string)env('APP_URL', ''), '/');
    if ($appUrl !== '' && str_starts_with($appUrl, 'https://')) {
        return 'https';
    }
    return 'http';
}

/**
 * Базовый origin приложения (без /api).
 */
function mfAppOrigin(): string
{
    $appUrl = rtrim((string)env('APP_URL', ''), '/');
    if ($appUrl !== '') {
        return $appUrl;
    }
    $scheme  = mfRequestScheme();
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $apiPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api')), '/');
    $root    = preg_replace('#/api$#', '', $apiPath);
    return $scheme . '://' . $host . ($root !== '' ? $root : '');
}

/**
 * Публичный URL файла в uploads/ (через api/media.php — надёжнее на shared-хостинге).
 */
function mfUploadPublicUrl(string $filename): string
{
    $filename = ltrim(str_replace('\\', '/', $filename), '/');
    return rtrim(mfAppOrigin(), '/') . '/api/media.php?f=' . rawurlencode($filename);
}

/**
 * Имя файла из /uploads/... или media.php?f=...
 */
function mfUploadsFilename(?string $url): ?string
{
    if ($url === null || trim($url) === '') {
        return null;
    }
    $url = trim($url);
    if (preg_match('#^(img|video|avatar|cover)_[A-Za-z0-9._-]+\.(jpe?g|png|gif|webp|mp4|webm|mov)$#i', $url)) {
        return $url;
    }
    if (preg_match('#/uploads/([^/?#\s]+)#i', $url, $m)) {
        return $m[1];
    }
    if (preg_match('#(?:^|[?&])f=([^&]+)#', $url, $m)) {
        return rawurldecode($m[1]);
    }
    return null;
}

/**
 * Нормализует URL аватара/лого/обложки из БД.
 */
function normalizeMediaUrl(?string $url): ?string
{
    if ($url === null) {
        return null;
    }
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    if (preg_match('#^(data:|blob:)#i', $url)) {
        return $url;
    }

    $origin = mfAppOrigin();
    $originParts = parse_url($origin);
    $originHost = $originParts['host'] ?? '';
    $originScheme = $originParts['scheme'] ?? 'https';

    $url = preg_replace('#(/api/uploads/)#', '/uploads/', $url);

    $filename = mfUploadsFilename($url);
    if ($filename !== null && $filename !== '') {
        return mfUploadPublicUrl($filename);
    }

    if ($url[0] === '/' || !preg_match('#^https?://#i', $url)) {
        $path = $url[0] === '/' ? $url : '/' . $url;
        $path = preg_replace('#^/api/uploads/#', '/uploads/', $path);
        return rtrim($origin, '/') . $path;
    }

    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return $url;
    }

    $path = $parts['path'] ?? '';
    $path = preg_replace('#^/api/uploads/#', '/uploads/', $path);

    if ($originHost !== '' && strcasecmp($parts['host'], $originHost) !== 0 && str_contains($path, '/uploads/')) {
        return rtrim($origin, '/') . $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }

    $scheme = $parts['scheme'] ?? 'http';
    if ($originScheme === 'https' && $scheme === 'http') {
        $scheme = 'https';
    }

    return $scheme . '://' . $parts['host']
        . (isset($parts['port']) ? ':' . $parts['port'] : '')
        . $path
        . (isset($parts['query']) ? '?' . $parts['query'] : '');
}
