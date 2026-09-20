<?php

declare(strict_types=1);

final class UploadPolicy
{
    public const MAX_BYTES = 7340032;

    public static function allowedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'video/mp4',
            'video/quicktime',
            'application/pdf',
        ];
    }

    public static function allowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'pdf'];
    }
}
