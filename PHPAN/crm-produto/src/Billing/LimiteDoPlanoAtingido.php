<?php

declare(strict_types=1);

namespace App\Billing;

use App\Domain\ErroDeDominio;

final class LimiteDoPlanoAtingido extends ErroDeDominio
{
    public static function clientes(int $limite): self
    {
        return new self("Limite do plano atingido ({$limite} clientes). Faça upgrade para continuar.");
    }

    public static function assinaturaInativa(): self
    {
        return new self('Assinatura inativa ou vencida. Regularize para continuar.');
    }
}
