<?php

declare(strict_types=1);

namespace App\Auditoria;

/**
 * Null object: usado em teste de unidade, onde não há banco e o rastro não é o
 * que está sendo verificado. Evita `?Auditoria $auditoria = null` e o `if` de
 * nulo espalhado em quem chama.
 */
final class AuditoriaNula implements Auditoria
{
    public function registrar(
        ?int $usuarioId,
        string $acao,
        string $entidade,
        int $entidadeId,
        ?array $dadosAntes = null,
        ?array $dadosDepois = null,
        ?string $ip = null,
    ): void {
        // no-op de propósito
    }

    /** @return list<array<string, mixed>> */
    public function historicoDe(string $entidade, int $entidadeId): array
    {
        return [];
    }
}
