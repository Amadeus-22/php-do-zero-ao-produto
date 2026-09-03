<?php

declare(strict_types=1);

namespace App\Exportacao;

use PDO;

/**
 * STREAMING: escreve linha a linha conforme o banco entrega, sem montar um
 * array com a tabela inteira. Com 50 registros os dois jeitos funcionam; com
 * 50 mil, o array estoura memory_limit e trava o request.
 */
final readonly class ExportadorDeClientesCsv
{
    /** Acima disto, a exportação vira job (Módulo 6, aula 2). */
    public const LIMITE_SINCRONO = 1000;

    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function total(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM clientes WHERE deletado_em IS NULL')->fetchColumn();
    }

    public function deveIrParaFila(): bool
    {
        return $this->total() > self::LIMITE_SINCRONO;
    }

    /**
     * @param resource $saida normalmente fopen('php://output', 'w')
     * @return int linhas escritas
     */
    public function escrever($saida): int
    {
        // BOM UTF-8: sem ele o Excel (sobretudo no Windows) abre acento quebrado
        fwrite($saida, "\xEF\xBB\xBF");
        fputcsv($saida, ['ID', 'Nome', 'E-mail', 'Telefone', 'Criado em'], ';', '"', '\\');

        $stmt = $this->pdo->query(
            'SELECT id, nome, email, telefone, criado_em FROM clientes WHERE deletado_em IS NULL ORDER BY nome',
        );

        if ($stmt === false) {
            return 0;
        }

        $linhas = 0;

        // fetch() em loop, não fetchAll(): cada linha sai na hora
        while (($linha = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            fputcsv($saida, $linha, ';', '"', '\\');
            $linhas++;
        }

        return $linhas;
    }
}
