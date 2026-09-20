<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$service = new AuthService();
$token = trim((string) ($_GET['token'] ?? ''));
$appUrl = rtrim(HostingConfig::app()['appUrl'] ?: 'http://localhost:8000', '/');

try {
    $service->verify($token);
    header('Location: ' . $appUrl . '/index.html?verified=1');
    exit;
} catch (Throwable $exception) {
    header('Location: ' . $appUrl . '/index.html?verified=error&message=' . urlencode($exception->getMessage()));
    exit;
}
