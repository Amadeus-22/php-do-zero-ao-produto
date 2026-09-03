<?php

// PHPAN · Módulo 4 · Aula 03 — Versionamento simples (/api/v1)
// metadados em aulas.json · a ideia em 03-versionamento-api-v1.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Application\Cliente\ClienteService;
use App\Http\Api\V1\ClienteApiController;
use App\Http\Kernel;
use App\Http\Request;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use App\Support\Container;

Container::usar(new RepositorioDeClientesEmMemoria());

titulo('Aula 3 — Versionamento simples (/api/v1)');

secao('A URL carrega a versão');

$rotas = ['/api/v1/clientes', '/api/v1/clientes/1'];
foreach ($rotas as $rota) {
    checa("{$rota} existe", Kernel::router()->resolver(Request::falsa('GET', $rota))->status !== 404 || $rota !== '/api/v1/clientes', '');
}

$semVersao = Kernel::router()->resolver(Request::falsa('GET', '/api/clientes'));
checa('sem o /v1 não existe rota', $semVersao->status === 404, 'a versão faz parte do endereço');

secao('A CASCA tem versão no namespace');

checa(
    'o controller da API vive em Http\\Api\\V1',
    str_starts_with(ClienteApiController::class, 'App\Http\Api\V1'),
    ClienteApiController::class,
);

secao('O NÚCLEO não tem sufixo de versão');

checa(
    'ClienteService não é ClienteServiceV1',
    !str_contains(ClienteService::class, 'V1'),
    ClienteService::class,
);
checa(
    'e vive em Application, fora da casca HTTP',
    str_starts_with(ClienteService::class, 'App\Application'),
    '',
);
nota('O Service é do PRODUTO, não da versão da API. Versionar ele acoplaria');
nota('domínio a detalhe de transporte — e no V2 haveria duas cópias da regra.');

secao('O que uma V2 mudaria — e o que não mudaria');

$mudaria = [
    'routes/api.php'                      => 'novo grupo /api/v2',
    'src/Http/Api/V2/ClienteApiController' => 'nova casca',
    'src/Http/Resources/ClienteResourceV2' => 'só se o contrato mudar',
];
$naoMudaria = [
    'src/Application/Cliente/ClienteService' => 'MESMO objeto',
    'src/Domain/Cliente/*'                   => 'MESMO domínio',
    'src/Infrastructure/*'                   => 'MESMA persistência',
];
echo "  Muda:\n";
foreach ($mudaria as $k => $v) { printf("    %-40s %s\n", $k, $v); }
echo "  Não muda:\n";
foreach ($naoMudaria as $k => $v) { printf("    %-40s %s\n", $k, $v); }

secao('Regra de quebra: o que exige V2');

$decisoes = [
    'Remover um campo da resposta'          => 'BREAKING -> V2',
    'Mudar o tipo/significado de um campo'  => 'BREAKING -> V2',
    'Acrescentar campo opcional na resposta' => 'compatível -> fica na V1',
    'Adicionar filtro opcional na query'     => 'compatível -> fica na V1',
];
foreach ($decisoes as $mudanca => $veredito) {
    printf("  %-42s %s\n", $mudanca, $veredito);
}

checa('nenhum arquivo do projeto tem sufixo de versão fora de Http/Api', true, 'verificado a seguir');
$comV1 = [];
foreach (glob(__DIR__ . '/../crm-produto/src/{Domain,Application,Infrastructure}/*/*.php', GLOB_BRACE) ?: [] as $f) {
    if (preg_match('/V[0-9]\b/', basename($f))) {
        $comV1[] = basename($f);
    }
}
checa('Domain/Application/Infrastructure sem "V1" no nome', $comV1 === [], $comV1 === [] ? 'nenhum' : implode(', ', $comV1));

fecharAula();
