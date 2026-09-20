<?php

declare(strict_types=1);

final class SessionGuard
{
    public static function requireAuthenticated(): string
    {
        $email = (string) ($_SESSION['pwe_user_email'] ?? '');
        if ($email === '') {
            throw new RuntimeException('Unauthorized.');
        }

        return $email;
    }
}
