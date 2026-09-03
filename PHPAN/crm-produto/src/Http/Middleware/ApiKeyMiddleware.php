<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\ApiError;
use App\Http\Request;
use App\Http\Response;

/** Ponte até o Módulo 5 (token de verdade). Chave fixa só para não deixar a API aberta. */
final class ApiKeyMiddleware implements Middleware
{
    public function handle(Request $request): ?Response
    {
        $esperada = (string) ($_ENV['API_KEY'] ?? getenv('API_KEY') ?: '');
        $enviada = (string) ($request->server['HTTP_X_API_KEY'] ?? '');

        if ($esperada === '' || !hash_equals($esperada, $enviada)) {
            return ApiError::make('unauthorized', 'Chave de API inválida.', 401);
        }

        return null;
    }
}
