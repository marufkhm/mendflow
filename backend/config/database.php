<?php

declare(strict_types=1);

require_once __DIR__ . '/hosting.php';

final class Database
{
    public static function config(): array
    {
        $hosting = HostingConfig::database();
        $host = $hosting['host'] ?: '127.0.0.1';
        $port = (int) ($hosting['port'] ?: 3306);
        $name = $hosting['name'] ?: 'pwe';
        $charset = $hosting['charset'] ?: 'utf8mb4';

        return [
            'dsn' => sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset),
            'user' => $hosting['user'] ?: 'root',
            'password' => $hosting['password'] ?: '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];
    }

    public static function pdo(): PDO
    {
        $config = self::config();

        return new PDO(
            $config['dsn'],
            $config['user'],
            $config['password'],
            $config['options']
        );
    }
}
