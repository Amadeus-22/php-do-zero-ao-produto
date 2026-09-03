<?php

declare(strict_types=1);

namespace App\Filas\Handlers;

use App\Domain\Notificacao\RemetenteDeEmail;
use App\Filas\JobHandler;
use PDO;

final readonly class NotificarLembrete implements JobHandler
{
    public function __construct(
        private PDO $pdo,
        private RemetenteDeEmail $remetente,
    ) {
    }

    public function tratar(array $payload): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.mensagem, u.email, u.nome, c.nome AS cliente_nome
               FROM lembretes l
               JOIN usuarios u ON u.id = l.usuario_id
               JOIN clientes c ON c.id = l.cliente_id
              WHERE l.id = :id',
        );
        $stmt->execute(['id' => $payload['lembrete_id']]);
        $dados = $stmt->fetch();

        if ($dados === false) {
            return;
        }

        $this->remetente->enviar(
            (string) $dados['email'],
            'Lembrete: ' . $dados['cliente_nome'],
            "Olá {$dados['nome']}, você tem um lembrete: {$dados['mensagem']}",
        );
    }
}
