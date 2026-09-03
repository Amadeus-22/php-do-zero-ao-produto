<?php

declare(strict_types=1);

namespace Tests\Domain\Usuario;

use App\Domain\Usuario\Papel;
use PHPUnit\Framework\TestCase;

final class PapelTest extends TestCase
{
    public function testSomenteAdminGerenciaUsuarios(): void
    {
        self::assertTrue(Papel::ADMIN->podeGerenciarUsuarios());
        self::assertFalse(Papel::VENDEDOR->podeGerenciarUsuarios());
        self::assertFalse(Papel::LEITURA->podeGerenciarUsuarios());
    }

    public function testLeituraNaoEdita(): void
    {
        self::assertTrue(Papel::ADMIN->podeEditar());
        self::assertTrue(Papel::VENDEDOR->podeEditar());
        self::assertFalse(Papel::LEITURA->podeEditar());
    }
}
