<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    $service = new AuthService();
    JsonResponse::send($service->stats());
} catch (Throwable $exception) {
    JsonResponse::error($exception->getMessage(), 500);
}
