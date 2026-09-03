<?php

// PHPAN · Módulo 3 · Aula 01 — O ciclo HTTP revisitado, com camadas
// metadados em aulas.json · a ideia em 01-ciclo-http-camadas.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;

// A partir do Módulo 5 a API exige Bearer e o painel exige sessão. Esta aula é
// anterior a isso: entra autenticada para exercitar as camadas.
bancoDaAula();
$token = tokenDeAula();
logadoNoPainel();

titulo('Aula 1 — O ciclo HTTP revisitado, com camadas');

secao('O caminho de GET /clientes, camada a camada');

$etapas = [
    '1. Requisição HTTP        ' => 'GET /clientes',
    '2. Front Controller       ' => 'public/index.php recebe TUDO',
    '3. Router                 ' => 'casa /clientes com ClienteController::index',
    '4. Controller             ' => 'traduz HTTP -> chamada de Service',
    '5. Service                ' => 'ClienteService::listar() — regra, sem $_POST',
    '6. Repository             ' => 'única camada que sabe que existe persistência',
    '7. Model/Entidade         ' => 'devolve objetos Cliente, não array cru',
    '8. Controller -> View     ' => 'passa os Clientes prontos para a view',
    '9. View                   ' => 'só percorre e imprime, com escape',
    '10. Response              ' => 'HTML de volta ao navegador',
];
foreach ($etapas as $etapa => $oque) {
    echo "  {$etapa} {$oque}\n";
}

secao('A regra prática: em que camada isso entra?');

$regra = [
    'Depende de $_GET/$_POST/cabeçalho HTTP?' => 'Controller',
    'É regra que o negócio exige, web ou API?' => 'Service',
    'É query SQL ou chamada ao PDO?'           => 'Repository',
    'É só dado, sem comportamento?'            => 'Model',
    'É só HTML/apresentação?'                  => 'View',
];
foreach ($regra as $pergunta => $camada) {
    printf("  %-42s -> %s\n", $pergunta, $camada);
}

secao('Rodando a requisição de verdade');

$router = Kernel::router();

$resposta = $router->resolver(Request::falsa('GET', '/clientes'));
checa('GET /clientes atravessa as camadas', $resposta->status === 200, "HTTP {$resposta->status}");
checa('a View devolveu HTML, não array', str_contains($resposta->body, '<h1>Clientes</h1>'), 'layout aplicado');

$resposta = $router->resolver(Request::falsa('GET', '/nao-existe'));
checa('rota inexistente devolve 404 previsível', $resposta->status === 404, 'e não erro fatal');

secao('A regra que existe UMA vez só');

// A mesma regra "e-mail não duplica" atende web E API — é a promessa da camada.
$viaApi = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Ana', 'email' => 'ana@exemplo.com']));
checa('API cria o cliente', $viaApi->status === 201, 'HTTP 201');

$duplicadoApi = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Outra', 'email' => 'ana@exemplo.com']));
checa('API recusa e-mail duplicado', $duplicadoApi->status === 409, 'HTTP 409 conflict');

nota('Nenhuma linha de regra foi reescrita: ClienteService é o mesmo nos dois.');
nota('Era exatamente isso que o clientes.php de 800 linhas não conseguia entregar.');

fecharAula();
