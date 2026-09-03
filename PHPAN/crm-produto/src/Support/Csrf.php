<?php

declare(strict_types=1);

namespace App\Support;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    public static function validar(mixed $token): bool
    {
        // hash_equals: comparação em tempo constante, não vaza informação por timing
        return is_string($token) && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }
}
