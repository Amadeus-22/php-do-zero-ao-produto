<?php

declare(strict_types=1);

namespace App\Filas\Handlers;

use App\Domain\Cliente\RepositorioDeClientes;
use App\Domain\Notificacao\RemetenteDeEmail;
use App\Filas\JobHandler;

final readonly class EnviarEmailBoasVindas implements JobHandler
{
    public function __construct(
        private RepositorioDeClientes $clientes,
        private RemetenteDeEmail $remetente,
    ) {
    }

    public function tratar(array $payload): void
    {
        $cliente = $this->clientes->buscarPorId((int) $payload['cliente_id']);

        // O cliente pode ter sido removido entre o despacho e o processamento.
        if ($cliente === null) {
            return;
        }

        $this->remetente->enviar(
            $cliente->email(),
            'Bem-vindo ao CRM',
            "Olá {$cliente->nome()}, seja bem-vindo.",
        );
    }
}
