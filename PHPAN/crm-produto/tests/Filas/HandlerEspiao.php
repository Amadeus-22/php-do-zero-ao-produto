<?php

declare(strict_types=1);

namespace Tests\Filas;

use App\Filas\JobHandler;
use RuntimeException;

/** Duplo de teste nomeado: conta execuções e, se pedido, falha de propósito. */
final class HandlerEspiao implements JobHandler
{
    public int $execucoes = 0;

    /** @var list<array<string, mixed>> */
    public array $payloads = [];

    public function __construct(
        private readonly ?string $falharCom = null,
    ) {
    }

    public function tratar(array $payload): void
    {
        $this->execucoes++;
        $this->payloads[] = $payload;

        if ($this->falharCom !== null) {
            throw new RuntimeException($this->falharCom);
        }
    }

    public function executou(): bool
    {
        return $this->execucoes > 0;
    }
}
