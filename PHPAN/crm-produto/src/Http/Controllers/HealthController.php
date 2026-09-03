<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\HealthCheck;
use App\Http\Request;
use App\Http\Response;

final class HealthController
{
    public function __invoke(Request $request): Response
    {
        $check = new HealthCheck();

        return Response::json($check->status(), $check->httpStatus());
    }
}
