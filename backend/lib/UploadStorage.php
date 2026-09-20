<?php

declare(strict_types=1);

final class UploadStorage
{
    public static function saveUploadedFile(array $file, string $bucket): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload failed.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > UploadPolicy::MAX_BYTES) {
            throw new RuntimeException('File exceeds 7 MB limit.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, UploadPolicy::allowedExtensions(), true)) {
            throw new RuntimeException('File extension is not allowed.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) ($file['tmp_name'] ?? ''));
        if (!in_array($mime, UploadPolicy::allowedMimeTypes(), true)) {
            throw new RuntimeException('File MIME type is not allowed.');
        }

        $baseDir = dirname(__DIR__, 2) . '/uploads/' . trim($bucket, '/') . '/';
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $newName = $bucket . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $baseDir . $newName;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new RuntimeException('Unable to store uploaded file.');
        }

        return '/uploads/' . trim($bucket, '/') . '/' . $newName;
    }
}
