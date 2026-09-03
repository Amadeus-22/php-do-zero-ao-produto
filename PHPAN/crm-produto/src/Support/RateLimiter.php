<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/**
 * Janela fixa. Não impede toda tentativa — torna força bruta lenta demais para
 * valer a pena, que é o objetivo real.
 *
 * Sem Redis: o volume de tentativas de login é baixo perto do tráfego geral, e
 * uma tabela resolve. Se Redis entrar depois, esta mesma interface troca de
 * implementação sem mexer em quem chama.
 */
final readonly class RateLimiter
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    /** Registra a tentativa e diz se a chamada DEVE ser bloqueada. */
    public function atingiu(string $chave, int $limite, int $janelaSegundos): bool
    {
        $inicio = date('Y-m-d H:i:s', time() - $janelaSegundos);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM tentativas_login WHERE chave = :chave AND criado_em > :inicio',
        );
        $stmt->execute(['chave' => $chave, 'inicio' => $inicio]);

        if ((int) $stmt->fetchColumn() >= $limite) {
            return true;
        }

        $this->pdo->prepare('INSERT INTO tentativas_login (chave) VALUES (:chave)')
            ->execute(['chave' => $chave]);

        return false;
    }

    /** Cron: sem isto a tabela cresce para sempre. */
    public function limparAntigos(int $maisAntigoQueSegundos = 86400): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM tentativas_login WHERE criado_em < :limite');
        $stmt->execute(['limite' => date('Y-m-d H:i:s', time() - $maisAntigoQueSegundos)]);

        return $stmt->rowCount();
    }
}
