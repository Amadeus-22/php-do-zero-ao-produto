<?php

declare(strict_types=1);

namespace App\Application\Contato;

use App\Domain\Cliente\ClienteNaoEncontrado;
use App\Domain\Cliente\RepositorioDeClientes;
use App\Domain\Contato\CanalPreferido;
use App\Domain\Contato\Contato;
use App\Domain\Contato\RepositorioDeContatos;

final readonly class CadastrarContato
{
    public function __construct(
        private RepositorioDeClientes $clientes,
        private RepositorioDeContatos $contatos,
    ) {
    }

    /** @throws ClienteNaoEncontrado|\App\Domain\Contato\ContatoInvalido */
    public function executar(
        int $clienteId,
        string $nome,
        string $email,
        CanalPreferido $canal,
    ): Contato {
        if ($this->clientes->buscarPorId($clienteId) === null) {
            throw ClienteNaoEncontrado::comId($clienteId);
        }

        return $this->contatos->salvar(
            new Contato(null, $clienteId, $nome, $email, $canal),
        );
    }
}
