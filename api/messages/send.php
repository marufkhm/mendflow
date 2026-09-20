<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    SessionGuard::requireAuthenticated();
    Csrf::assertRequest();

    $service = new MessageService();
    JsonResponse::send($service->send($_POST, $_FILES), 201);
} catch (Throwable $exception) {
    JsonResponse::error($exception->getMessage(), 422);
}
