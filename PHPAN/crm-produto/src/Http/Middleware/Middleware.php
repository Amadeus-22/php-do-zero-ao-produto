<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

/**
 * null      -> segue para o próximo middleware ou para o controller
 * Response  -> a cadeia PARA aqui; o controller nem é chamado
 */
interface Middleware
{
    public function handle(Request $request): ?Response;
}
