<?php

declare(strict_types=1);

namespace App\Infrastructure\Lembrete;

use App\Domain\Lembrete\RepositorioDeLembretes;
use DateTimeImmutable;
use PDO;

final readonly class RepositorioDeLembretesPdo implements RepositorioDeLembretes
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function criar(int $usuarioId, int $clienteId, string $mensagem, DateTimeImmutable $venceEmUtc): int
    {
        $this->pdo->prepare(
            'INSERT INTO lembretes (usuario_id, cliente_id, mensagem, vence_em) VALUES (:u, :c, :m, :v)',
        )->execute([
            'u' => $usuarioId,
            'c' => $clienteId,
            'm' => $mensagem,
            'v' => $venceEmUtc->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Seleciona e marca na MESMA transação — é o que impede dois crons de
     * notificarem o mesmo lembrete.
     *
     * @return list<int>
     */
    public function reservarVencidos(): array
    {
        $this->pdo->beginTransaction();

        // `<=` e não `=`: um cron que ficou fora do ar ainda pega os atrasados.
        $stmt = $this->pdo->query(
            "SELECT id FROM lembretes
              WHERE status = 'pendente' AND vence_em <= UTC_TIMESTAMP()
              FOR UPDATE SKIP LOCKED",
        );
        $ids = $stmt === false ? [] : array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        foreach ($ids as $id) {
            $this->pdo->prepare("UPDATE lembretes SET status = 'notificado' WHERE id = :id")->execute(['id' => $id]);
        }

        $this->pdo->commit();

        return $ids;
    }

    /** @return list<array<string, mixed>> */
    public function pendentesDe(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM lembretes WHERE usuario_id = :u AND status != 'concluido' ORDER BY vence_em",
        );
        $stmt->execute(['u' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function concluir(int $id, int $usuarioId): void
    {
        $this->pdo->prepare("UPDATE lembretes SET status = 'concluido' WHERE id = :id AND usuario_id = :u")
            ->execute(['id' => $id, 'u' => $usuarioId]);
    }
}
