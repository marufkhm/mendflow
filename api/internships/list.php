<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$service = new InternshipService();
JsonResponse::send($service->list(Request::query()));
