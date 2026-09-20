<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$service = new AuthService();
$payload = Request::json();

try {
    JsonResponse::send($service->login($payload));
} catch (Throwable $exception) {
    JsonResponse::send([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], 401);
}
