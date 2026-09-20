<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    $service = new FeedService();
    JsonResponse::send($service->list(Request::query()));
} catch (Throwable $exception) {
    $status = (int) $exception->getCode();
    JsonResponse::error($exception->getMessage(), $status >= 400 ? $status : 401);
}
