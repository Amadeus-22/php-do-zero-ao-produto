<?php

declare(strict_types=1);

namespace App\Http;

final class ApiError
{
    /** @param array<string, mixed>|null $details */
    public static function make(string $code, string $message, int $status, ?array $details = null): Response
    {
        $erro = ['code' => $code, 'message' => $message];

        if ($details !== null && $details !== []) {
            $erro['details'] = $details;
        }

        return Response::json(['error' => $erro], $status);
    }
}
