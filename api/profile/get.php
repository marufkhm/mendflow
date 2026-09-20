<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    $service = new ProfileService();
    JsonResponse::send($service->getCurrent());
} catch (Throwable $exception) {
    JsonResponse::error($exception->getMessage(), 401);
}
