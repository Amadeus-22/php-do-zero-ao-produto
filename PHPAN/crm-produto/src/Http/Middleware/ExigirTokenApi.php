<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\ApiError;
use App\Http\Request;
use App\Http\Response;
use App\Support\Container;

/** Autenticação da API: Bearer, nunca cookie. Uma rota, um mecanismo. */
final class ExigirTokenApi implements Middleware
{
    /** Preenchido para os middlewares/controllers seguintes saberem quem é. */
    public static ?int $usuarioId = null;

    public function handle(Request $request): ?Response
    {
        self::$usuarioId = null;

        $cabecalho = (string) ($request->server['HTTP_AUTHORIZATION'] ?? '');

        if (!str_starts_with($cabecalho, 'Bearer ')) {
            return ApiError::make('unauthorized', 'Token ausente.', 401);
        }

        $usuarioId = Container::tokenService()->validarAccess(substr($cabecalho, 7));

        if ($usuarioId === null) {
            return ApiError::make('unauthorized', 'Token inválido ou expirado.', 401);
        }

        self::$usuarioId = $usuarioId;

        return null;
    }
}
