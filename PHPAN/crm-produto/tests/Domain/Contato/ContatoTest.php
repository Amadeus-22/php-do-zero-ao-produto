<?php

declare(strict_types=1);

namespace Tests\Domain\Contato;

use App\Domain\Contato\CanalPreferido;
use App\Domain\Contato\Contato;
use App\Domain\Contato\ContatoInvalido;
use PHPUnit\Framework\TestCase;

final class ContatoTest extends TestCase
{
    public function testEmailInvalidoLancaExcecao(): void
    {
        $this->expectException(ContatoInvalido::class);

        new Contato(null, 1, 'Bruno', 'bruno-sem-arroba', CanalPreferido::EMAIL);
    }

    public function testNomeVazioLancaExcecao(): void
    {
        $this->expectException(ContatoInvalido::class);

        new Contato(null, 1, '   ', 'bruno@exemplo.com', CanalPreferido::EMAIL);
    }

    public function testAlterarCanalPreferido(): void
    {
        $contato = new Contato(null, 1, 'Bruno', 'bruno@exemplo.com', CanalPreferido::EMAIL);
        $contato->alterarCanalPreferido(CanalPreferido::WHATSAPP);

        self::assertSame(CanalPreferido::WHATSAPP, $contato->canalPreferido());
    }
}
