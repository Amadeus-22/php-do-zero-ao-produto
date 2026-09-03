<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\PaginaDeErro;
use App\Http\Request;
use App\Http\Response;

/** Assume que AuthMiddleware JÁ rodou — por isso a ordem no array de rota importa. */
final class AdminMiddleware implements Middleware
{
    public function handle(Request $request): ?Response
    {
        if (($_SESSION['papel'] ?? null) !== 'admin') {
            return PaginaDeErro::acessoRestrito();
        }

        return null;
    }
}
