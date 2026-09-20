<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

JsonResponse::send([
    'ok' => true,
    'token' => Csrf::token(),
]);
