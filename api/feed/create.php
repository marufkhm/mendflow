<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    SessionGuard::requireAuthenticated();
    Csrf::assertRequest();

    $service = new FeedService();
    JsonResponse::send($service->create($_POST, $_FILES), 201);
} catch (Throwable $exception) {
    $status = (int) $exception->getCode();
    JsonResponse::error($exception->getMessage(), $status >= 400 ? $status : 422);
}
