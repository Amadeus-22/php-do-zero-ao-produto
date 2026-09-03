<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

final class AuthMiddleware implements Middleware
{
    public function handle(Request $request): ?Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['usuario_id'])) {
            return Response::redirect('/login');
        }

        return null;
    }
}
