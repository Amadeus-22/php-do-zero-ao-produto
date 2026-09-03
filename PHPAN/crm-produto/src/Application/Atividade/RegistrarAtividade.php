<?php

declare(strict_types=1);

namespace App\Application\Atividade;

use App\Domain\Atividade\Atividade;
use App\Domain\Atividade\AtividadeInvalida;
use App\Domain\Atividade\RepositorioDeAtividades;
use App\Domain\Atividade\TipoAtividade;
use App\Domain\Cliente\RepositorioDeClientes;

final readonly class RegistrarAtividade
{
    public function __construct(
        private RepositorioDeClientes $clientes,
        private RepositorioDeAtividades $atividades,
    ) {
    }

    /** @throws AtividadeInvalida */
    public function executar(
        int $clienteId,
        TipoAtividade $tipo,
        string $descricao,
    ): Atividade {
        if ($this->clientes->buscarPorId($clienteId) === null) {
            throw AtividadeInvalida::clienteInexistente($clienteId);
        }

        return $this->atividades->salvar(
            new Atividade(null, $clienteId, $tipo, $descricao, new \DateTimeImmutable()),
        );
    }
}
