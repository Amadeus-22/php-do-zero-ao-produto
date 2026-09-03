<?php

declare(strict_types=1);

namespace App\Application\Lembrete;

use App\Domain\Lembrete\RepositorioDeLembretes;
use App\Filas\JobDispatcher;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Fuso é o que mais gera bug aqui: grava-se SEMPRE em UTC e converte só na
 * exibição. Salvar hora local funciona na sua máquina e quebra quando servidor
 * e usuário estão em fusos diferentes, ou no horário de verão.
 */
final readonly class LembreteService
{
    public function __construct(
        private RepositorioDeLembretes $lembretes,
        private JobDispatcher $dispatcher,
    ) {
    }

    public function criar(int $usuarioId, int $clienteId, string $mensagem, DateTimeImmutable $venceEmLocal): int
    {
        return $this->lembretes->criar(
            $usuarioId,
            $clienteId,
            $mensagem,
            $venceEmLocal->setTimezone(new DateTimeZone('UTC')),
        );
    }

    /** Roda em cron. Só DESPACHA: quem envia o e-mail é o worker. */
    public function despacharVencidos(): int
    {
        $ids = $this->lembretes->reservarVencidos();

        foreach ($ids as $id) {
            $this->dispatcher->despachar('notificar_lembrete', ['lembrete_id' => $id]);
        }

        return count($ids);
    }

    /** @return list<array<string, mixed>> com a data já convertida para exibição */
    public function pendentesDe(int $usuarioId, string $fusoExibicao = 'America/Sao_Paulo'): array
    {
        return array_map(
            static function (array $l) use ($fusoExibicao): array {
                $utc = new DateTimeImmutable((string) $l['vence_em'], new DateTimeZone('UTC'));
                $l['vence_em_local'] = $utc->setTimezone(new DateTimeZone($fusoExibicao))->format('d/m/Y H:i');

                return $l;
            },
            $this->lembretes->pendentesDe($usuarioId),
        );
    }

    public function concluir(int $id, int $usuarioId): void
    {
        $this->lembretes->concluir($id, $usuarioId);
    }
}
