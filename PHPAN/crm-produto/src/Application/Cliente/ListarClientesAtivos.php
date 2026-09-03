<?php

declare(strict_types=1);

namespace App\Application\Cliente;

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\RepositorioDeClientes;

final readonly class ListarClientesAtivos
{
    public function __construct(
        private RepositorioDeClientes $repositorio,
    ) {
    }

    /** @return list<Cliente> */
    public function executar(): array
    {
        return $this->repositorio->todosAtivos();
    }
}
