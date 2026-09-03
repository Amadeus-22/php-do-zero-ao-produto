<?php

declare(strict_types=1);

namespace App\Domain\Atividade;

use App\Domain\ErroDeDominio;

final class AtividadeInvalida extends ErroDeDominio
{
    public static function descricaoVazia(): self
    {
        return new self('Descrição da atividade é obrigatória.');
    }

    public static function clienteInexistente(int $clienteId): self
    {
        return new self("Não é possível registrar atividade: cliente {$clienteId} não existe.");
    }
}
