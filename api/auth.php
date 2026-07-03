<?php
/**
 * auth.php — Bearer token middleware для realtime API (presence, typing, sse).
 * Устанавливает $authUser или отправляет 401.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$userId   = verifyToken();
$authUser = getUser($userId);

if (!$authUser || !is_array($authUser)) {
    http_response_code(401);
    echo json_encode(['error' => 'Пользователь не найден']);
    exit;
}

$authUser['id'] = (int)$authUser['id'];
