<?php

declare(strict_types=1);

final class HostingConfig
{
    public static function app(): array
    {
        return [
            'appUrl' => 'https://your-subdomain.infinityfreeapp.com',
            'corsOrigin' => 'https://your-subdomain.infinityfreeapp.com',
        ];
    }

    public static function database(): array
    {
        return [
            'host' => 'sql207.infinityfree.com',
            'port' => 3306,
            'name' => 'if0_41616496_db_mendflow',
            'charset' => 'utf8mb4',
            'user' => 'if0_41616496',
            'password' => 'jZDmxPM7CX7o',
        ];
    }

    public static function mail(): array
    {
        return [
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'fromEmail' => '',
            'fromName' => 'PWE',
        ];
    }
}
