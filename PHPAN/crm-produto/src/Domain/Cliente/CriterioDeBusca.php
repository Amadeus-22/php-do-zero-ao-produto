<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

/** Critério de listagem. Vive no domínio porque descreve O QUE se busca, não COMO. */
final readonly class CriterioDeBusca
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $q = null,
        public ?bool $ativo = null,
    ) {
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
