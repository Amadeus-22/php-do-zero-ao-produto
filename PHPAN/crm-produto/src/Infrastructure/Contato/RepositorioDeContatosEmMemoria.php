<?php

declare(strict_types=1);

namespace App\Infrastructure\Contato;

use App\Domain\Contato\Contato;
use App\Domain\Contato\RepositorioDeContatos;

final class RepositorioDeContatosEmMemoria implements RepositorioDeContatos
{
    /** @var array<int, Contato> */
    private array $contatos = [];

    private int $proximoId = 1;

    public function salvar(Contato $contato): Contato
    {
        $id = $contato->id() ?? $this->proximoId++;

        $salvo = new Contato(
            $id,
            $contato->clienteId(),
            $contato->nome(),
            $contato->email(),
            $contato->canalPreferido(),
        );
        $this->contatos[$id] = $salvo;

        return $salvo;
    }

    /** @return list<Contato> */
    public function doCliente(int $clienteId): array
    {
        return array_values(array_filter(
            $this->contatos,
            static fn (Contato $c): bool => $c->clienteId() === $clienteId,
        ));
    }
}
