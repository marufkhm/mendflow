<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$service = new AuthService();
$payload = Request::json();

try {
    JsonResponse::send($service->register($payload), 201);
} catch (Throwable $exception) {
    JsonResponse::send([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
