<?php

declare(strict_types=1);

namespace App\Auth;

use App\Domain\Usuario\Usuario;

final readonly class ResultadoDeLogin
{
    private function __construct(
        public bool $autenticado,
        public bool $bloqueado,
        public ?Usuario $usuario = null,
        public ?int $tenteEmSegundos = null,
    ) {
    }

    public static function sucesso(Usuario $usuario): self
    {
        return new self(true, false, $usuario);
    }

    public static function credenciaisInvalidas(): self
    {
        return new self(false, false);
    }

    public static function bloqueado(int $tenteEmSegundos): self
    {
        return new self(false, true, null, $tenteEmSegundos);
    }
}
