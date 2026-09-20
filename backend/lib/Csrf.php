<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['pwe_csrf'])) {
            $_SESSION['pwe_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['pwe_csrf'];
    }

    public static function assertRequest(): void
    {
        $method = Request::method();
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $headerToken = Request::header('X-CSRF-Token') ?? '';
        if (!hash_equals(self::token(), $headerToken)) {
            throw new RuntimeException('Invalid CSRF token.');
        }
    }
}
