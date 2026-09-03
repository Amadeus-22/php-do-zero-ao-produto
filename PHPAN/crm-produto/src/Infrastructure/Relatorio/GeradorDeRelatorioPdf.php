<?php

declare(strict_types=1);

namespace App\Infrastructure\Relatorio;

use App\Domain\Relatorio\GeradorDeRelatorio;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * dompdf: HTML -> PDF, sem motor de browser. Resolve relatório simples sem a
 * complexidade (e o consumo) de um headless Chrome.
 *
 * A MESMA interface do CSV — quem consome não sabe qual formato está usando.
 */
final class GeradorDeRelatorioPdf implements GeradorDeRelatorio
{
    /** @param list<array<string, scalar>> $linhas */
    public function gerar(string $titulo, array $linhas): string
    {
        $opcoes = new Options();
        // isRemoteEnabled fica FALSE: com ele ligado, um dado do relatório com
        // <img src="http://..."> faria o servidor buscar URL arbitrária (SSRF).
        $opcoes->set('isRemoteEnabled', false);
        $opcoes->set('defaultFont', 'DejaVu Sans'); // acento sem virar caixinha

        $dompdf = new Dompdf($opcoes);
        $dompdf->loadHtml($this->html($titulo, $linhas), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    public function extensaoArquivo(): string
    {
        return 'pdf';
    }

    /** @param list<array<string, scalar>> $linhas */
    private function html(string $titulo, array $linhas): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $html = '<html><head><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:11px}'
            . 'h1{font-size:16px} table{width:100%;border-collapse:collapse}'
            . 'th,td{border:1px solid #999;padding:4px;text-align:left}'
            . 'th{background:#eee}'
            . '</style></head><body>';

        $html .= '<h1>' . $e($titulo) . '</h1>';
        $html .= '<p>Gerado em ' . $e((new \DateTimeImmutable())->format('d/m/Y H:i')) . '</p>';

        if ($linhas === []) {
            return $html . '<p>Sem registros.</p></body></html>';
        }

        $html .= '<table><thead><tr>';

        foreach (array_keys($linhas[0]) as $coluna) {
            $html .= '<th>' . $e($coluna) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($linhas as $linha) {
            $html .= '<tr>';

            // Escape em TODA célula: o dado vem do banco, mas o banco recebeu do
            // usuário. Um nome com <script> não executa em PDF, mas quebra o layout.
            foreach ($linha as $valor) {
                $html .= '<td>' . $e($valor) . '</td>';
            }

            $html .= '</tr>';
        }

        return $html . '</tbody></table></body></html>';
    }
}
