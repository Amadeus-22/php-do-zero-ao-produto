<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Usuario\Gate;
use App\Http\ApiError;
use App\Http\Request;
use App\Http\Response;
use App\Support\Container;

/**
 * Autorização da API. Assume que ExigirTokenApi JÁ rodou — daí a ordem no array
 * de rota importar: autenticar antes de autorizar.
 */
final class ExigirPapel implements Middleware
{
    public function __construct(
        private readonly string $acao,
    ) {
    }

    public function handle(Request $request): ?Response
    {
        $usuarioId = ExigirTokenApi::$usuarioId;

        if ($usuarioId === null) {
            return ApiError::make('unauthorized', 'Não autenticado.', 401);
        }

        $usuario = Container::repositorioDeUsuarios()->buscarPorId($usuarioId);

        if ($usuario === null) {
            return ApiError::make('unauthorized', 'Usuário não encontrado.', 401);
        }

        if (!(new Gate())->pode($usuario->papel(), $this->acao)) {
            // 403, não 401: ele É quem diz ser, mas não pode fazer isso.
            return ApiError::make('forbidden', 'Seu papel não permite esta ação.', 403);
        }

        return null;
    }
}
