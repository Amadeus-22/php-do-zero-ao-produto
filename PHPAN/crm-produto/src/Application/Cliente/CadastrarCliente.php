<?php

declare(strict_types=1);

namespace App\Application\Cliente;

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\RepositorioDeClientes;

final readonly class CadastrarCliente
{
    // O tipo é a INTERFACE, não uma implementação concreta.
    public function __construct(
        private RepositorioDeClientes $repositorio,
    ) {
    }

    /** @throws \App\Domain\Cliente\ClienteInvalido */
    public function executar(string $nome, string $email): Cliente
    {
        return $this->repositorio->salvar(Cliente::novo($nome, $email));
    }
}
