<?php

declare(strict_types=1);

namespace App\Filas;

use App\Log\Logger;
use PDO;
use Throwable;

final readonly class Worker
{
    public const MAX_TENTATIVAS = 5;

    /** @param array<string, JobHandler> $handlers */
    public function __construct(
        private PDO $pdo,
        private array $handlers,
        private Logger $logger,
    ) {
    }

    /** Processa um job. Devolve false quando não havia nada a fazer. */
    public function processarProximo(): bool
    {
        $this->pdo->beginTransaction();

        // FOR UPDATE SKIP LOCKED: dois workers em paralelo não pegam o MESMO job.
        // Sem isso, o e-mail de boas-vindas sai duas vezes.
        $stmt = $this->pdo->query(
            "SELECT * FROM jobs
              WHERE status = 'pendente' AND disponivel_em <= NOW()
              ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED",
        );
        $job = $stmt === false ? false : $stmt->fetch();

        if ($job === false) {
            $this->pdo->commit();

            return false;
        }

        $this->pdo->prepare("UPDATE jobs SET status = 'processando' WHERE id = :id")->execute(['id' => $job['id']]);
        $this->pdo->commit();

        try {
            $handler = $this->handlers[$job['tipo']]
                ?? throw new \RuntimeException("handler não encontrado: {$job['tipo']}");

            $handler->tratar(json_decode((string) $job['payload'], true, flags: JSON_THROW_ON_ERROR));

            $this->pdo->prepare("UPDATE jobs SET status = 'concluido', concluido_em = NOW() WHERE id = :id")
                ->execute(['id' => $job['id']]);

            $this->logger->info('job concluído', ['job_id' => (int) $job['id'], 'tipo' => $job['tipo']]);
        } catch (Throwable $e) {
            $this->falhou($job, $e);
        }

        return true;
    }

    /** @param array<string, mixed> $job */
    private function falhou(array $job, Throwable $e): void
    {
        $tentativas = (int) $job['tentativas'] + 1;

        if ($tentativas >= self::MAX_TENTATIVAS) {
            // dead-letter: para de tentar e fica para alguém olhar
            $this->pdo->prepare("UPDATE jobs SET status = 'falhou', tentativas = :t, erro = :erro WHERE id = :id")
                ->execute(['t' => $tentativas, 'erro' => $e->getMessage(), 'id' => $job['id']]);

            $this->logger->error('job esgotou as tentativas', [
                'job_id' => (int) $job['id'],
                'tipo' => $job['tipo'],
                'erro' => $e->getMessage(),
            ]);

            return;
        }

        // backoff exponencial: 60s, 120s, 240s... e não um loop imediato
        $atraso = 30 * (2 ** $tentativas);

        $this->pdo->prepare(
            "UPDATE jobs SET status = 'pendente', tentativas = :t, erro = :erro,
                    disponivel_em = DATE_ADD(NOW(), INTERVAL :atraso SECOND)
              WHERE id = :id",
        )->execute(['t' => $tentativas, 'erro' => $e->getMessage(), 'atraso' => $atraso, 'id' => $job['id']]);

        $this->logger->warning('job falhou, será retentado', [
            'job_id' => (int) $job['id'],
            'tentativa' => $tentativas,
            'proximo_em_s' => $atraso,
        ]);
    }
}
