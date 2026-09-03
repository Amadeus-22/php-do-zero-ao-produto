<?php

declare(strict_types=1);

namespace App\Domain\Atividade;

interface RepositorioDeAtividades
{
    public function salvar(Atividade $atividade): Atividade;

    /** @return list<Atividade> */
    public function doCliente(int $clienteId): array;
}
