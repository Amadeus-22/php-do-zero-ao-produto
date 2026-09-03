<?php

declare(strict_types=1);

namespace App\Auth;

use DateInterval;
use DateTimeImmutable;
use PDO;

/**
 * Tokens OPACOS guardados como hash.
 *
 * Por que não JWT auto-contido: JWT sem estado no servidor não pode ser revogado
 * antes de expirar — a não ser mantendo uma blocklist, e aí você já guarda estado
 * e perdeu a vantagem. Token opaco é uma string aleatória sem significado, e o
 * banco decide se ainda vale.
 */
final readonly class TokenService
{
    private const DURACAO_ACCESS = 'PT15M';  // curto: janela pequena se vazar
    private const DURACAO_REFRESH = 'P30D';  // longo: só serve para renovar

    public function __construct(
        private PDO $pdo,
    ) {
    }

    /** @return array{access: string, refresh: string} */
    public function emitirPar(int $usuarioId): array
    {
        return [
            'access' => $this->emitir($usuarioId, 'access', self::DURACAO_ACCESS),
            'refresh' => $this->emitir($usuarioId, 'refresh', self::DURACAO_REFRESH),
        ];
    }

    public function validarAccess(string $tokenBruto): ?int
    {
        return $this->usuarioDeTokenValido($tokenBruto, 'access');
    }

    /**
     * ROTAÇÃO: revoga o refresh usado e emite um par novo.
     * Sem isso, um refresh vazado uma vez valeria para sempre; com rotação, o
     * estrago fica limitado a uma única janela.
     */
    public function renovar(string $refreshBruto): ?array
    {
        $linha = $this->linhaDeTokenValido($refreshBruto, 'refresh');

        if ($linha === null) {
            return null;
        }

        $this->pdo->prepare('UPDATE tokens SET revogado_em = NOW() WHERE id = :id')
            ->execute(['id' => $linha['id']]);

        return $this->emitirPar((int) $linha['usuario_id']);
    }

    /** Logout de verdade: derruba access e refresh do usuário no SERVIDOR. */
    public function revogarTodosDoUsuario(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tokens SET revogado_em = NOW() WHERE usuario_id = :id AND revogado_em IS NULL',
        );
        $stmt->execute(['id' => $usuarioId]);

        return $stmt->rowCount();
    }

    private function emitir(int $usuarioId, string $tipo, string $duracao): string
    {
        $tokenBruto = bin2hex(random_bytes(32)); // 64 chars de entropia criptográfica
        $expiraEm = (new DateTimeImmutable())->add(new DateInterval($duracao));

        $this->pdo->prepare(
            'INSERT INTO tokens (usuario_id, tipo, token_hash, expira_em) VALUES (:uid, :tipo, :hash, :expira)',
        )->execute([
            'uid' => $usuarioId,
            'tipo' => $tipo,
            'hash' => self::hash($tokenBruto), // o banco NUNCA vê o token em claro
            'expira' => $expiraEm->format('Y-m-d H:i:s'),
        ]);

        return $tokenBruto; // única vez que ele existe legível: na resposta ao cliente
    }

    private function usuarioDeTokenValido(string $tokenBruto, string $tipo): ?int
    {
        $linha = $this->linhaDeTokenValido($tokenBruto, $tipo);

        return $linha === null ? null : (int) $linha['usuario_id'];
    }

    /** @return array<string, mixed>|null */
    private function linhaDeTokenValido(string $tokenBruto, string $tipo): ?array
    {
        // As TRÊS condições importam: hash bate, não foi revogado, e não expirou.
        // Esquecer "expira_em > NOW()" faz token expirado continuar funcionando.
        $stmt = $this->pdo->prepare(
            'SELECT id, usuario_id FROM tokens
              WHERE token_hash = :hash AND tipo = :tipo
                AND revogado_em IS NULL AND expira_em > NOW()',
        );
        $stmt->execute(['hash' => self::hash($tokenBruto), 'tipo' => $tipo]);
        $linha = $stmt->fetch();

        return $linha === false ? null : $linha;
    }

    private static function hash(string $tokenBruto): string
    {
        return hash('sha256', $tokenBruto);
    }
}
