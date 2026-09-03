<?php

declare(strict_types=1);

namespace App\Support;

final class Flash
{
    public static function sucesso(string $mensagem): void
    {
        self::definir('sucesso', $mensagem);
    }

    public static function erro(string $mensagem): void
    {
        self::definir('erro', $mensagem);
    }

    private static function definir(string $tipo, string $mensagem): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }
}
