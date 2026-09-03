<?php

declare(strict_types=1);

namespace App\Filas;

interface JobHandler
{
    /** @param array<string, mixed> $payload */
    public function tratar(array $payload): void;
}
