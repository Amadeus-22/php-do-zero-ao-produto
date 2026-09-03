<?php

declare(strict_types=1);

namespace App\Infrastructure\Atividade;

use App\Domain\Atividade\Atividade;
use App\Domain\Atividade\RepositorioDeAtividades;

final class RepositorioDeAtividadesEmMemoria implements RepositorioDeAtividades
{
    /** @var array<int, Atividade> */
    private array $atividades = [];

    private int $proximoId = 1;

    public function salvar(Atividade $atividade): Atividade
    {
        $id = $atividade->id() ?? $this->proximoId++;

        $salva = new Atividade(
            $id,
            $atividade->clienteId(),
            $atividade->tipo(),
            $atividade->descricao(),
            $atividade->ocorridaEm(),
        );
        $this->atividades[$id] = $salva;

        return $salva;
    }

    /** @return list<Atividade> */
    public function doCliente(int $clienteId): array
    {
        return array_values(array_filter(
            $this->atividades,
            static fn (Atividade $a): bool => $a->clienteId() === $clienteId,
        ));
    }
}
