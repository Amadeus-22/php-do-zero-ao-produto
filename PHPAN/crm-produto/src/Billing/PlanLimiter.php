<?php

declare(strict_types=1);

namespace App\Billing;

use PDO;

/**
 * "Este cliente pagante tem direito a X até quando?"
 *
 * A checagem tem que rodar em TODO ponto de entrada que cria o recurso limitado.
 * Validar só no formulário web e esquecer a API significa que o limite não
 * existe — existe só na experiência que você lembrou de proteger.
 */
final readonly class PlanLimiter
{
    /** Dias de tolerância antes de bloquear escrita numa assinatura atrasada. */
    public const GRACE_DIAS = 7;

    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function podeCriarCliente(int $contaId): bool
    {
        return $this->contarClientes($contaId) < $this->limiteDe($contaId, 'max_clientes');
    }

    public function podeCriarUsuario(int $contaId): bool
    {
        return $this->contarUsuarios($contaId) < $this->limiteDe($contaId, 'max_usuarios');
    }

    /** Atraso não trava na hora: bloqueia escrita só depois do grace period. */
    public function podeEscrever(int $contaId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT status, DATEDIFF(CURDATE(), atrasada_desde) AS dias
               FROM assinaturas WHERE conta_id = :conta ORDER BY id DESC LIMIT 1',
        );
        $stmt->execute(['conta' => $contaId]);
        $assinatura = $stmt->fetch();

        if ($assinatura === false || $assinatura['status'] === 'cancelada') {
            return false;
        }

        if ($assinatura['status'] === 'atrasada') {
            return (int) ($assinatura['dias'] ?? 0) <= self::GRACE_DIAS;
        }

        return true;
    }

    public function limiteDe(int $contaId, string $coluna): int
    {
        // whitelist: o nome da coluna entra na query, então NÃO pode vir de fora
        if (!in_array($coluna, ['max_clientes', 'max_usuarios'], true)) {
            throw new \InvalidArgumentException("Limite desconhecido: {$coluna}");
        }

        $stmt = $this->pdo->prepare(
            "SELECT p.{$coluna}
               FROM assinaturas a
               JOIN planos p ON p.id = a.plano_id
              WHERE a.conta_id = :conta AND a.status IN ('ativa', 'atrasada')
              ORDER BY a.id DESC LIMIT 1",
        );
        $stmt->execute(['conta' => $contaId]);
        $valor = $stmt->fetchColumn();

        // sem assinatura ativa = zero acesso, não acesso ilimitado
        return $valor === false ? 0 : (int) $valor;
    }

    public function contarClientes(int $contaId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM clientes WHERE conta_id = :conta AND deletado_em IS NULL');
        $stmt->execute(['conta' => $contaId]);

        return (int) $stmt->fetchColumn();
    }

    public function contarUsuarios(int $contaId): int
    {
        // usuarios ainda não tem conta_id: conta única nesta fase (ver .md)
        return (int) $this->pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }
}
