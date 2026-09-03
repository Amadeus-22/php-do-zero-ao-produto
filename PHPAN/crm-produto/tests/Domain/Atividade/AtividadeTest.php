<?php

declare(strict_types=1);

namespace Tests\Domain\Atividade;

use App\Domain\Atividade\Atividade;
use App\Domain\Atividade\AtividadeInvalida;
use App\Domain\Atividade\TipoAtividade;
use PHPUnit\Framework\TestCase;

final class AtividadeTest extends TestCase
{
    public function testDescricaoVaziaLancaExcecao(): void
    {
        $this->expectException(AtividadeInvalida::class);

        new Atividade(null, 1, TipoAtividade::LIGACAO, '  ', new \DateTimeImmutable());
    }

    public function testAtividadeValidaGuardaOsDados(): void
    {
        $quando = new \DateTimeImmutable('2026-08-31 10:00:00');
        $atividade = new Atividade(null, 7, TipoAtividade::REUNIAO, 'Kickoff do projeto', $quando);

        self::assertSame(7, $atividade->clienteId());
        self::assertSame(TipoAtividade::REUNIAO, $atividade->tipo());
        self::assertSame($quando, $atividade->ocorridaEm());
    }
}
