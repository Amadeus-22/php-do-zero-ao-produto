<?php

declare(strict_types=1);

namespace App\Infrastructure\Relatorio;

use App\Domain\Relatorio\GeradorDeRelatorio;

final class GeradorDeRelatorioCsv implements GeradorDeRelatorio
{
    /** @param list<array<string, scalar>> $linhas */
    public function gerar(string $titulo, array $linhas): string
    {
        if ($linhas === []) {
            return '';
        }

        $buffer = fopen('php://temp', 'r+');

        if ($buffer === false) {
            return '';
        }

        fputcsv($buffer, array_keys($linhas[0]), ',', '"', '\\');

        foreach ($linhas as $linha) {
            fputcsv($buffer, $linha, ',', '"', '\\');
        }

        rewind($buffer);
        $conteudo = stream_get_contents($buffer);
        fclose($buffer);

        return $conteudo === false ? '' : $conteudo;
    }

    public function extensaoArquivo(): string
    {
        return 'csv';
    }
}
