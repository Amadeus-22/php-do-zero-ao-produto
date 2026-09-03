<?php

declare(strict_types=1);

namespace Tests\Exportacao;

use App\Exportacao\ExportadorDeClientesCsv;
use App\Support\Container;
use Tests\Support\BancoDeTeste;

final class ExportacaoTest extends BancoDeTeste
{
    private function csv(): string
    {
        $buffer = fopen('php://temp', 'r+');
        self::assertNotFalse($buffer);

        (new ExportadorDeClientesCsv($this->pdo))->escrever($buffer);
        rewind($buffer);
        $conteudo = (string) stream_get_contents($buffer);
        fclose($buffer);

        return $conteudo;
    }

    public function testComecaComBomUtf8(): void
    {
        self::assertStringStartsWith("\xEF\xBB\xBF", $this->csv(), 'sem BOM o Excel quebra acentuação');
    }

    public function testTemCabecalhoELinhas(): void
    {
        Container::clienteService()->criar(['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com']);

        $csv = $this->csv();

        self::assertStringContainsString('ID;Nome;E-mail', $csv);
        self::assertStringContainsString('Ana Souza', $csv);
    }

    public function testNaoExportaClienteNaLixeira(): void
    {
        $service = Container::clienteService();
        $id = $service->criar(['nome' => 'Removido', 'email' => 'removido@exemplo.com'])->id();
        self::assertNotNull($id);
        $service->criar(['nome' => 'Ativo', 'email' => 'ativo@exemplo.com']);
        $service->remover($id);

        $csv = $this->csv();

        self::assertStringContainsString('Ativo', $csv);
        self::assertStringNotContainsString('Removido', $csv);
    }

    public function testDecideEntreSincronoEFilaPeloVolume(): void
    {
        $exportador = new ExportadorDeClientesCsv($this->pdo);

        self::assertFalse($exportador->deveIrParaFila(), 'base pequena sai na hora');
        self::assertSame(1000, ExportadorDeClientesCsv::LIMITE_SINCRONO);
    }
}
