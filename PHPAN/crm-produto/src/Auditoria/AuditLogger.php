<?php

declare(strict_types=1);

namespace App\Auditoria;

use PDO;

/**
 * Rastro de NEGÓCIO, não log de debug.
 *
 * Log de aplicação é para dev, pode rotacionar e sumir em semanas. Auditoria
 * responde "quem excluiu o cliente X" daqui a um ano — é append-only e não se
 * apaga. Por isso esta classe só tem INSERT: nenhum UPDATE, nenhum DELETE.
 */
final readonly class AuditLogger implements Auditoria
{
    /** Campos que NUNCA entram no rastro, mesmo que venham no array. */
    private const SENSIVEIS = ['senha', 'senha_hash', 'token', 'token_hash', 'refresh', 'access', 'cartao', 'cvv'];

    public function __construct(
        private PDO $pdo,
    ) {
    }

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
    ): void {
        $this->pdo->prepare(
            'INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, dados_antes, dados_depois, ip)
             VALUES (:usuario_id, :acao, :entidade, :entidade_id, :antes, :depois, :ip)',
        )->execute([
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'antes' => self::json($dadosAntes),
            'depois' => self::json($dadosDepois),
            'ip' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function historicoDe(string $entidade, int $entidadeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM auditoria WHERE entidade = :e AND entidade_id = :id ORDER BY criado_em DESC, id DESC',
        );
        $stmt->execute(['e' => $entidade, 'id' => $entidadeId]);

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed>|null $dados */
    private static function json(?array $dados): ?string
    {
        if ($dados === null) {
            return null;
        }

        foreach (array_keys($dados) as $campo) {
            if (in_array(strtolower((string) $campo), self::SENSIVEIS, true)) {
                unset($dados[$campo]);
            }
        }

        return json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
