<?php

declare(strict_types=1);

namespace App\Domain\Lembrete;

use DateTimeImmutable;

/**
 * Contrato de persistência dos lembretes.
 *
 * Existe porque o LembreteService estava em Application/ conversando com PDO
 * direto — violando a regra do Módulo 1 (aplicação não conhece infraestrutura).
 * Quem pegou foi a própria aula 3 do Módulo 1, que varre a pasta procurando PDO.
 */
interface RepositorioDeLembretes
{
    public function criar(int $usuarioId, int $clienteId, string $mensagem, DateTimeImmutable $venceEmUtc): int;

    /** @return list<int> ids dos vencidos, já marcados como notificados */
    public function reservarVencidos(): array;

    /** @return list<array<string, mixed>> */
    public function pendentesDe(int $usuarioId): array;

    public function concluir(int $id, int $usuarioId): void;
}
