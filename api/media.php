<?php
/**
 * Отдача файлов из uploads/ — обход ограничений .htaccess на хостинге.
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

$file = basename((string)($_GET['f'] ?? ''));
if ($file === '' || preg_match('/[\/\\\\]/', $file)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$path = mfUploadsDir() . $file;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'mov'  => 'video/quicktime',
];
$mime = $types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string)filesize($path));

if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    exit;
}

readfile($path);
