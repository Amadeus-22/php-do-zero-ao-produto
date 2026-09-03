<?php

declare(strict_types=1);

namespace App\Domain\Contato;

interface RepositorioDeContatos
{
    public function salvar(Contato $contato): Contato;

    /** @return list<Contato> */
    public function doCliente(int $clienteId): array;
}
