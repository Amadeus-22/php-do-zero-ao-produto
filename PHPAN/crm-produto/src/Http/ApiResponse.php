<?php

declare(strict_types=1);

namespace App\Http;

final class ApiResponse
{
    public static function ok(mixed $data, int $status = 200): Response
    {
        return Response::json(['data' => $data], $status);
    }

    /** @param array<string, mixed> $meta */
    public static function lista(mixed $data, array $meta, int $status = 200): Response
    {
        return Response::json(['data' => $data, 'meta' => $meta], $status);
    }
}
