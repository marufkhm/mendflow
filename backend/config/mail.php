<?php

declare(strict_types=1);

require_once __DIR__ . '/hosting.php';

final class MailConfig
{
    public static function settings(): array
    {
        $hosting = HostingConfig::mail();

        return [
            'host' => $hosting['host'] ?: '',
            'port' => (int) ($hosting['port'] ?: 587),
            'encryption' => $hosting['encryption'] ?: 'tls',
            'username' => $hosting['username'] ?: '',
            'password' => $hosting['password'] ?: '',
            'fromEmail' => $hosting['fromEmail'] ?: '',
            'fromName' => $hosting['fromName'] ?: 'PWE',
        ];
    }
}
