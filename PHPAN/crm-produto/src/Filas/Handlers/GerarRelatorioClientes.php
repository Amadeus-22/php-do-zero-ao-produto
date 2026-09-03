<?php

declare(strict_types=1);

namespace App\Filas\Handlers;

use App\Domain\Cliente\RepositorioDeClientes;
use App\Domain\Relatorio\GeradorDeRelatorio;
use App\Filas\JobHandler;

final readonly class GerarRelatorioClientes implements JobHandler
{
    public function __construct(
        private RepositorioDeClientes $clientes,
        private GeradorDeRelatorio $gerador,
        private string $diretorio,
    ) {
    }

    public function tratar(array $payload): void
    {
        $caminho = sprintf('%s/clientes-%s.%s', $this->diretorio, $payload['referencia'], $this->gerador->extensaoArquivo());

        // IDEMPOTÊNCIA: se o job rodar duas vezes (retry, worker duplicado), o
        // relatório não é recriado — e dois workers não brigam pelo arquivo.
        if (is_file($caminho)) {
            return;
        }

        $linhas = array_map(
            static fn ($c): array => [
                'id' => (int) $c->id(),
                'nome' => $c->nome(),
                'email' => $c->email(),
                'criado_em' => $c->criadoEm()->format('Y-m-d'),
            ],
            $this->clientes->todos(),
        );

        if (!is_dir($this->diretorio)) {
            mkdir($this->diretorio, 0o775, true);
        }

        file_put_contents($caminho, $this->gerador->gerar('Clientes', $linhas));
    }
}
