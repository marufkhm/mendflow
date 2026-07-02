<?php
/**
 * Sprint 19 — Web Push helper.
 */

function ensurePushTables(PDO $pdo): void {
    static $done = false;
    if ($done) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            endpoint VARCHAR(512) NOT NULL,
            p256dh VARCHAR(255) NOT NULL,
            auth VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_push_endpoint (endpoint(191)),
            KEY idx_push_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $done = true;
}

function mfSendWebPush(PDO $pdo, int $userId, string $title, string $body, string $url = './'): void {
    $publicKey  = env('VAPID_PUBLIC_KEY');
    $privateKey = env('VAPID_PRIVATE_KEY');
    $subject    = env('VAPID_SUBJECT', 'mailto:noreply@mendflow.app');

    if (!$publicKey || !$privateKey) {
        return;
    }

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_readable($autoload)) {
        return;
    }

    require_once $autoload;

    ensurePushTables($pdo);

    $stmt = $pdo->prepare('SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        return;
    }

    $auth = [
        'VAPID' => [
            'subject'    => $subject,
            'publicKey'  => $publicKey,
            'privateKey' => $privateKey,
        ],
    ];

    $webPush = new Minishlink\WebPush\WebPush($auth);
    $payload = json_encode([
        'title' => $title,
        'body'  => $body,
        'url'   => $url,
    ], JSON_UNESCAPED_UNICODE);

    foreach ($rows as $row) {
        $webPush->queueNotification(
            Minishlink\WebPush\Subscription::create([
                'endpoint' => $row['endpoint'],
                'keys'     => [
                    'p256dh' => $row['p256dh'],
                    'auth'   => $row['auth'],
                ],
            ]),
            $payload
        );
    }

    foreach ($webPush->flush() as $report) {
        if ($report->isSuccess()) {
            continue;
        }
        $endpoint = $report->getRequest()?->getUri()?->__toString();
        if ($endpoint) {
            $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?')->execute([$endpoint]);
        }
    }
}
