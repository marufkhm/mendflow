<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../backend/config/hosting.php';

$allowedOrigin = HostingConfig::app()['corsOrigin'] ?: '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/mail.php';
require_once __DIR__ . '/../backend/lib/JsonResponse.php';
require_once __DIR__ . '/../backend/lib/Request.php';
require_once __DIR__ . '/../backend/lib/Csrf.php';
require_once __DIR__ . '/../backend/lib/SessionGuard.php';
require_once __DIR__ . '/../backend/lib/SmtpMailer.php';
require_once __DIR__ . '/../backend/lib/UploadPolicy.php';
require_once __DIR__ . '/../backend/lib/UploadStorage.php';
require_once __DIR__ . '/../backend/services/ProfileService.php';
require_once __DIR__ . '/../backend/services/AuthService.php';
require_once __DIR__ . '/../backend/services/FeedService.php';
require_once __DIR__ . '/../backend/services/CommunityService.php';
require_once __DIR__ . '/../backend/services/InternshipService.php';
require_once __DIR__ . '/../backend/services/MessageService.php';
require_once __DIR__ . '/../backend/services/ProjectWorkspaceService.php';
