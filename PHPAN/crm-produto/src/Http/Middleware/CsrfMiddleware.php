<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\PaginaDeErro;
use App\Http\Request;
use App\Http\Response;
use App\Support\Csrf;

final class CsrfMiddleware implements Middleware
{
    public function handle(Request $request): ?Response
    {
        // GET/HEAD não alteram estado: nada a validar.
        if (!in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        if (!Csrf::validar($request->input('_token'))) {
            return PaginaDeErro::sessaoExpirada();
        }

        return null;
    }
}
