<?php

declare(strict_types=1);

namespace App\Uploads;

final readonly class UploadResultado
{
    public function __construct(
        public string $nomeOriginal,
        public string $nomeArmazenado,
        public string $mimeReal,
        public int $tamanhoBytes,
    ) {
    }
}
