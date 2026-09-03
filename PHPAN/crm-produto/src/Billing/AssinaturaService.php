<?php

declare(strict_types=1);

namespace App\Billing;

use PDO;

final readonly class AssinaturaService
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function ativar(int $assinaturaId): void
    {
        $this->pdo->prepare(
            "UPDATE assinaturas SET status = 'ativa', atrasada_desde = NULL,
                    renova_em = DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
              WHERE id = :id",
        )->execute(['id' => $assinaturaId]);
    }

    public function marcarAtrasada(int $assinaturaId): void
    {
        $this->pdo->prepare(
            "UPDATE assinaturas SET status = 'atrasada', atrasada_desde = COALESCE(atrasada_desde, CURDATE())
              WHERE id = :id",
        )->execute(['id' => $assinaturaId]);
    }

    public function cancelar(int $assinaturaId): void
    {
        $this->pdo->prepare("UPDATE assinaturas SET status = 'cancelada' WHERE id = :id")
            ->execute(['id' => $assinaturaId]);
    }
}
