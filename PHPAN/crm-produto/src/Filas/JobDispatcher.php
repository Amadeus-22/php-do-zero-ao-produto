<?php

declare(strict_types=1);

namespace App\Filas;

use PDO;

/**
 * A ação principal só REGISTRA a intenção e devolve resposta na hora. Mandar
 * e-mail dentro do request de "criar cliente" faz o usuário esperar o SMTP e
 * obriga a decidir, ali, se uma falha de envio derruba a criação do cliente —
 * nenhuma resposta boa.
 */
final readonly class JobDispatcher
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function despachar(string $tipo, array $payload): int
    {
        $this->pdo->prepare('INSERT INTO jobs (tipo, payload) VALUES (:tipo, :payload)')->execute([
            'tipo' => $tipo,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
