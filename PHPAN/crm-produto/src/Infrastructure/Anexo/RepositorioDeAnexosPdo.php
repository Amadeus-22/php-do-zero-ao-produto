<?php

declare(strict_types=1);

namespace App\Infrastructure\Anexo;

use App\Domain\Anexo\RepositorioDeAnexos;
use PDO;

final readonly class RepositorioDeAnexosPdo implements RepositorioDeAnexos
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function doCliente(int $clienteId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM anexos WHERE cliente_id = :id ORDER BY id DESC');
        $stmt->execute(['id' => $clienteId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM anexos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch();

        return $linha === false ? null : $linha;
    }

    public function registrar(int $clienteId, string $nomeOriginal, string $nomeArmazenado, string $mimeReal, int $tamanhoBytes, ?int $criadoPor): int
    {
        $this->pdo->prepare(
            'INSERT INTO anexos (cliente_id, nome_original, nome_armazenado, mime_real, tamanho_bytes, criado_por)
             VALUES (:cliente, :original, :armazenado, :mime, :tamanho, :por)',
        )->execute([
            'cliente' => $clienteId,
            'original' => $nomeOriginal,
            'armazenado' => $nomeArmazenado,
            'mime' => $mimeReal,
            'tamanho' => $tamanhoBytes,
            'por' => $criadoPor,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
