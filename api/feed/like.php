<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    SessionGuard::requireAuthenticated();
    Csrf::assertRequest();

    $payload = Request::json();
    $service = new FeedService();
    JsonResponse::send($service->like((int) ($payload['postId'] ?? 0)));
} catch (Throwable $exception) {
    $status = (int) $exception->getCode();
    JsonResponse::error($exception->getMessage(), $status >= 400 ? $status : 400);
}
