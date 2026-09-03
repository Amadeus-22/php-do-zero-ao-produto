<?php

// PHPAN · Módulo 2 · Aula 03 — Interfaces e contratos
// metadados em aulas.json · a ideia em 03-interfaces-contratos.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Application\Cliente\ListarClientesAtivos;
use App\Domain\Cliente\RepositorioDeClientes;
use App\Domain\Notificacao\RemetenteDeEmail;
use App\Domain\Relatorio\GeradorDeRelatorio;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use App\Infrastructure\Cliente\RepositorioDeClientesPdo;
use App\Infrastructure\Notificacao\RemetenteDeEmailEmLog;
use App\Infrastructure\Relatorio\GeradorDeRelatorioCsv;
use App\Support\Container;

$pdo = bancoDaAula();

titulo('Aula 3 — Interfaces e contratos');

secao('A interface diz O QUE, não COMO');

$contrato = new ReflectionClass(RepositorioDeClientes::class);

checa('é uma interface', $contrato->isInterface(), '');
checa('só declara assinaturas', array_filter($contrato->getMethods(), static fn (ReflectionMethod $m): bool => !$m->isAbstract()) === [], count($contrato->getMethods()) . ' métodos');

$fonte = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Domain/Cliente/RepositorioDeClientes.php');
foreach (['PDO', 'SELECT', 'mysql', 'json'] as $detalhe) {
    checa("o contrato não menciona {$detalhe}", stripos($fonte, $detalhe) === false, '');
}

secao('O GANHO: trocar a implementação sem tocar em quem usa');

$emMemoria = new RepositorioDeClientesEmMemoria();
$comBanco = new RepositorioDeClientesPdo($pdo);

foreach ([$emMemoria, $comBanco] as $repo) {
    checa('cumpre o contrato: ' . (new ReflectionClass($repo))->getShortName(), $repo instanceof RepositorioDeClientes, '');
}

// O MESMO caso de uso, sem nenhuma alteração, com as duas implementações:
Container::usar($emMemoria);
Container::clienteService()->criar(['nome' => 'Ana (memória)', 'email' => 'memoria@exemplo.com']);
$daMemoria = (new ListarClientesAtivos($emMemoria))->executar();

Container::usar($comBanco);
Container::clienteService()->criar(['nome' => 'Ana (banco)', 'email' => 'banco@exemplo.com']);
$doBanco = (new ListarClientesAtivos($comBanco))->executar();

checa('ListarClientesAtivos funciona em memória', count($daMemoria) === 1, $daMemoria[0]->nome());
checa('e igual com PDO/MySQL', count($doBanco) === 1, $doBanco[0]->nome());
nota('Nenhuma linha do caso de uso mudou entre as duas execuções. Ele tipa a');
nota('INTERFACE, não a implementação — o "D" do SOLID sem container sofisticado.');

secao('Por que o teste unitário depende disso');

$inicio = microtime(true);
for ($i = 0; $i < 200; $i++) {
    $emMemoria->salvar(App\Domain\Cliente\Cliente::novo("Cliente {$i}", "c{$i}@exemplo.com"));
}
$tempoMemoria = round((microtime(true) - $inicio) * 1000, 1);

checa('200 inserções em memória', $tempoMemoria < 200, "{$tempoMemoria}ms");
nota('Em milissegundos, sem banco configurado e sem estado externo. É por isso');
nota('que a implementação em memória existe: ela é o duplo dos testes.');

secao('Injeção de dependência: o tipo do parâmetro é a INTERFACE');

$param = (new ReflectionClass(ListarClientesAtivos::class))->getConstructor()?->getParameters()[0];
checa(
    'ListarClientesAtivos recebe RepositorioDeClientes',
    (string) $param?->getType() === RepositorioDeClientes::class,
    'não RepositorioDeClientesPdo',
);
nota('Se tipasse a classe concreta, o teste ficaria preso a um banco real.');

secao('Outros contratos do projeto');

$remetente = new RemetenteDeEmailEmLog(sys_get_temp_dir() . '/aula-email.log');
checa('RemetenteDeEmailEmLog implementa a interface', $remetente instanceof RemetenteDeEmail, 'dev: grava em log');
nota('Em produção entra SMTP/API — o resto do sistema não muda.');

$gerador = new GeradorDeRelatorioCsv();
checa('GeradorDeRelatorioCsv implementa a interface', $gerador instanceof GeradorDeRelatorio, '');

$csv = $gerador->gerar('Clientes', [['id' => 1, 'nome' => 'Ana Souza']]);
checa('gera CSV com cabeçalho', str_contains($csv, 'id,nome'), '');
checa('e informa a extensão', $gerador->extensaoArquivo() === 'csv', '');
nota('Quem recebe GeradorDeRelatorio funciona igual para CSV, PDF ou formato');
nota('futuro — sem if ($formato === "csv") espalhado pelo sistema.');

secao('Interface vs classe abstrata');

printf("  %-16s %-38s %s\n", '', 'INTERFACE', 'CLASSE ABSTRATA');
printf("  %-16s %-38s %s\n", 'Define', 'só o contrato', 'contrato + implementação parcial');
printf("  %-16s %-38s %s\n", 'Quantas', 'várias por classe', 'PHP só permite estender UMA');
printf("  %-16s %-38s %s\n", 'Quando', 'contrato entre camadas', 'família "é um" com código comum');

secao('QUANDO NÃO criar interface');

nota('Interface para tudo, sem segunda implementação prevista, é over-engineering.');
nota('Crie quando existe (ou logo existirá) mais de uma implementação, OU quando');
nota('o contrato cruza fronteira de camada: domínio -> infraestrutura.');

$interfaces = glob(__DIR__ . '/../crm-produto/src/Domain/*/Repositorio*.php') ?: [];
checa('as interfaces do domínio são de fronteira', count($interfaces) >= 4, count($interfaces) . ' contratos de repositório');

fecharAula();
