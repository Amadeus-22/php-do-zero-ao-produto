<?php

declare(strict_types=1);

namespace App\Domain\Atividade;

final class Atividade
{
    /** @throws AtividadeInvalida */
    public function __construct(
        private readonly ?int $id,
        private readonly int $clienteId,
        private readonly TipoAtividade $tipo,
        private readonly string $descricao,
        private readonly \DateTimeImmutable $ocorridaEm,
    ) {
        if (trim($descricao) === '') {
            throw AtividadeInvalida::descricaoVazia();
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function tipo(): TipoAtividade
    {
        return $this->tipo;
    }

    public function descricao(): string
    {
        return $this->descricao;
    }

    public function ocorridaEm(): \DateTimeImmutable
    {
        return $this->ocorridaEm;
    }
}
