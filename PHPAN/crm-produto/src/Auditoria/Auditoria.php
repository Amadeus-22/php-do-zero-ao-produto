<?php

declare(strict_types=1);

namespace App\Auditoria;

interface Auditoria
{
    /**
     * @param array<string, mixed>|null $dadosAntes
     * @param array<string, mixed>|null $dadosDepois
     */
    public function registrar(
        ?int $usuarioId,
        string $acao,
        string $entidade,
        int $entidadeId,
        ?array $dadosAntes = null,
        ?array $dadosDepois = null,
        ?string $ip = null,
    ): void;

    /** @return list<array<string, mixed>> Do mais recente para o mais antigo. */
    public function historicoDe(string $entidade, int $entidadeId): array;
}
