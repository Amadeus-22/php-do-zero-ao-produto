<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

/**
 * Conceito de papel modelado desde o Módulo 1 (aula 4).
 * A FISCALIZAÇÃO das permissões só chega no Módulo 5 — aqui é só o conceito.
 */
enum Papel: string
{
    case ADMIN = 'admin';
    case VENDEDOR = 'vendedor';
    case LEITURA = 'leitura';

    public function podeEditar(): bool
    {
        return match ($this) {
            self::ADMIN, self::VENDEDOR => true,
            self::LEITURA => false,
        };
    }

    public function podeGerenciarUsuarios(): bool
    {
        return $this === self::ADMIN;
    }
}
