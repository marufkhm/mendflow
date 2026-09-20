<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    SessionGuard::requireAuthenticated();
    Csrf::assertRequest();

    $service = new ProfileService();
    JsonResponse::send($service->uploadAvatar($_FILES['avatar'] ?? []));
} catch (Throwable $exception) {
    JsonResponse::error($exception->getMessage(), 400);
}
