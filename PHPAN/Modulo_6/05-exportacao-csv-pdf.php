<?php

// PHPAN · Módulo 6 · Aula 05 — Exportação CSV e PDF
// metadados em aulas.json · a ideia em 05-exportacao-csv-pdf.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Usuario\Gate;
use App\Domain\Usuario\Papel;
use App\Exportacao\ExportadorDeClientesCsv;
use App\Support\Container;

$pdo = bancoDaAula();
$service = Container::clienteService();

foreach ([['Ana Souza', 'ana@exemplo.com'], ['Bruno Lima', 'bruno@exemplo.com'], ['Cecília Ávila', 'cecilia@exemplo.com']] as [$nome, $email]) {
    $service->criar(['nome' => $nome, 'email' => $email]);
}

$exportador = new ExportadorDeClientesCsv($pdo);

$gerar = static function () use ($exportador): string {
    $buffer = fopen('php://temp', 'r+');
    $exportador->escrever($buffer);
    rewind($buffer);
    $csv = (string) stream_get_contents($buffer);
    fclose($buffer);

    return $csv;
};

titulo('Aula 5 — Exportação CSV e PDF');

secao('STREAMING: escreve conforme o banco entrega');

// php_strip_whitespace remove comentários: sem isso o teste leria a própria
// linha que EXPLICA por que não se usa fetchAll, e acusaria falso positivo.
$fonte = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Exportacao/ExportadorDeClientesCsv.php');

checa('usa fetch() em loop', str_contains($fonte, '$stmt->fetch(PDO::FETCH_ASSOC)'), 'uma linha por vez');
checa('NÃO usa fetchAll()', !str_contains($fonte, 'fetchAll'), 'array com a tabela toda estoura memory_limit');
nota('Com 50 registros os dois jeitos funcionam. Com 50 mil, um deles trava o request.');

secao('BOM UTF-8: o detalhe que evita "Cecília Ávila" virar lixo');

$csv = $gerar();

checa('arquivo começa com o BOM', str_starts_with($csv, "\xEF\xBB\xBF"), 'EF BB BF');
checa('e o acento sobrevive', str_contains($csv, 'Cecília Ávila'), '');
nota('Sem o BOM, o Excel (sobretudo no Windows) interpreta como outra codificação.');

secao('Conteúdo');

checa('tem cabeçalho', str_contains($csv, 'ID;Nome;E-mail'), 'separador ; para o Excel pt-BR');
checa('tem as 3 linhas', substr_count(trim($csv), "\n") === 3, 'cabeçalho + 3 clientes');

echo "\n  Primeiras linhas:\n";
foreach (array_slice(explode("\n", $csv), 0, 3) as $linha) {
    echo '    ', str_replace("\xEF\xBB\xBF", '', $linha), "\n";
}

secao('Soft delete respeitado na exportação');

$removido = $service->criar(['nome' => 'Fantasma', 'email' => 'fantasma@exemplo.com']);
$service->remover((int) $removido->id());

$csv = $gerar();
checa('cliente na lixeira não é exportado', !str_contains($csv, 'Fantasma'), 'WHERE deletado_em IS NULL');

secao('Síncrono ou fila? Depende do volume');

checa('base pequena sai na hora', !$exportador->deveIrParaFila(), $exportador->total() . ' clientes');
checa('o corte é ' . ExportadorDeClientesCsv::LIMITE_SINCRONO, ExportadorDeClientesCsv::LIMITE_SINCRONO === 1000, '');
nota('Acima do limite a rota devolve 202 e despacha o job — o usuário não fica');
nota('esperando 40 segundos com a página travada.');

secao('Exportação é ação SENSÍVEL');

$gate = new Gate();
printf("  %-12s %s\n", 'admin', $gate->pode(Papel::ADMIN, 'cliente.exportar') ? 'pode' : 'não pode');
printf("  %-12s %s\n", 'vendedor', $gate->pode(Papel::VENDEDOR, 'cliente.exportar') ? 'pode' : 'não pode');
printf("  %-12s %s\n", 'leitura', $gate->pode(Papel::LEITURA, 'cliente.exportar') ? 'pode' : 'não pode');

checa('leitura NÃO exporta', !$gate->pode(Papel::LEITURA, 'cliente.exportar'), '');
nota('Dado exportado sai do controle do sistema: vira arquivo no computador de');
nota('alguém, fora de qualquer log ou permissão.');

secao('Nome do arquivo vindo da requisição');

$fonteResponse = (string) file_get_contents(__DIR__ . '/../crm-produto/src/Http/Response.php');
checa('Content-Disposition passa por basename()', str_contains($fonteResponse, 'basename($nome)'), 'evita injeção de cabeçalho');

secao('PDF');

nota('dompdf (HTML -> PDF, sem motor de browser) resolve relatório simples.');
nota('Ainda não instalado aqui: o CSV cobre a entrega e o PDF é o próximo passo.');
nota('A regra que vale para os dois: se passar de poucos segundos, vai para a fila.');

fecharAula();
