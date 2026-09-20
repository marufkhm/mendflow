<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    SessionGuard::requireAuthenticated();
    $service = new ProjectWorkspaceService();
    JsonResponse::send($service->list(Request::query()));
} catch (Throwable $exception) {
    JsonResponse::error($exception->getMessage(), 401);
}
