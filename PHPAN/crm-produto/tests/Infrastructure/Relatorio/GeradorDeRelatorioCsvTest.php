<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Relatorio;

use App\Infrastructure\Relatorio\GeradorDeRelatorioCsv;
use PHPUnit\Framework\TestCase;

final class GeradorDeRelatorioCsvTest extends TestCase
{
    public function testListaVaziaGeraStringVazia(): void
    {
        self::assertSame('', (new GeradorDeRelatorioCsv())->gerar('Clientes', []));
    }

    public function testGeraCabecalhoELinhas(): void
    {
        $csv = (new GeradorDeRelatorioCsv())->gerar('Clientes', [
            ['id' => 1, 'nome' => 'Ana Souza'],
            ['id' => 2, 'nome' => 'Bruno Lima'],
        ]);

        self::assertStringContainsString('id,nome', $csv);
        self::assertStringContainsString('1,"Ana Souza"', $csv);
        self::assertSame('csv', (new GeradorDeRelatorioCsv())->extensaoArquivo());
    }
}
