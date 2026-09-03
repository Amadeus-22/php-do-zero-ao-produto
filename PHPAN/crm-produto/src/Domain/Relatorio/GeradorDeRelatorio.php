<?php

declare(strict_types=1);

namespace App\Domain\Relatorio;

interface GeradorDeRelatorio
{
    /** @param list<array<string, scalar>> $linhas */
    public function gerar(string $titulo, array $linhas): string;

    public function extensaoArquivo(): string;
}
