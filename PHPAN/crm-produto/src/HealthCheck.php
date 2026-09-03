<?php

declare(strict_types=1);

namespace App;

use App\Config\Config;
use App\Support\Database;
use Throwable;

/**
 * Responde "estou vivo E minhas dependências estão" — não apenas "o processo PHP
 * respondeu", que o próprio servidor web já garante sozinho.
 *
 * Sem autenticação (monitor externo precisa bater sem token), mas sem vazar
 * detalhe de infraestrutura: "banco falhou" sim; host, usuário e stack trace, não.
 */
final class HealthCheck
{
    private const DISCO_MINIMO_BYTES = 100 * 1024 * 1024;

    /** @return array{status: string, checks: array<string, string>, timestamp: string} */
    public function status(): array
    {
        $checks = [
            'database' => $this->banco(),
            'disk' => $this->disco(),
            'queue' => $this->fila(),
        ];

        return [
            'status' => in_array('fail', $checks, true) ? 'degraded' : 'ok',
            'checks' => $checks,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    public function httpStatus(): int
    {
        return $this->status()['status'] === 'ok' ? 200 : 503;
    }

    private function banco(): string
    {
        try {
            Config::carregar();
            Database::conexao()->query('SELECT 1');

            return 'ok';
        } catch (Throwable) {
            // a mensagem da exceção traria host e usuário do banco: não sai daqui
            return 'fail';
        }
    }

    private function disco(): string
    {
        $livre = disk_free_space(__DIR__);

        return $livre !== false && $livre > self::DISCO_MINIMO_BYTES ? 'ok' : 'low';
    }

    /** Fila entupida é degradação silenciosa: o site responde, mas nada é processado. */
    private function fila(): string
    {
        try {
            $pendentes = (int) Database::conexao()
                ->query("SELECT COUNT(*) FROM jobs WHERE status = 'pendente' AND disponivel_em < DATE_SUB(NOW(), INTERVAL 15 MINUTE)")
                ->fetchColumn();

            return $pendentes > 100 ? 'atrasada' : 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }
}
