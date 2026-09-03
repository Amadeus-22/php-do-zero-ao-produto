<?php

// PHPAN · Módulo 3 · Aula 03 — Controllers finos, Services gordos
// metadados em aulas.json · a ideia em 03-controllers-services.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Application\Cliente\ClienteService;
use App\Domain\Cliente\ClienteNaoEncontrado;
use App\Domain\Cliente\EmailJaCadastrado;
use App\Http\Kernel;
use App\Http\Request;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use App\Support\Container;

// Primeira parte: o Service ISOLADO, com duplo em memória — sem HTTP, sem banco.
// É esse o ponto da aula: a regra roda sem infraestrutura nenhuma.
$repositorio = new RepositorioDeClientesEmMemoria();

titulo('Aula 3 — Controllers finos, Services gordos');

secao('O Service sozinho: sem HTTP, sem Response, sem $_POST');

$service = new ClienteService($repositorio);

$cliente = $service->criar(['nome' => '  Ana Souza ', 'email' => 'ANA@Exemplo.com ', 'telefone' => null]);
checa('criar() normaliza e persiste', $cliente->id() === 1, "id={$cliente->id()}");
checa('e-mail vira minúsculo', $cliente->email() === 'ana@exemplo.com', $cliente->email());

checaExcecao(
    'e-mail duplicado é recusado pelo Service',
    EmailJaCadastrado::class,
    static fn () => $service->criar(['nome' => 'Outra', 'email' => 'ana@exemplo.com']),
);

checaExcecao(
    'buscar() inexistente lança erro de domínio',
    ClienteNaoEncontrado::class,
    static fn () => $service->buscar(404),
);

$atualizado = $service->atualizar(1, ['nome' => 'Ana S. Souza', 'email' => 'ana@exemplo.com']);
checa('atualizar() aceita o MESMO e-mail do próprio cliente', $atualizado->nome() === 'Ana S. Souza', 'sem falso conflito');
checa('atualizar() preserva criadoEm', $atualizado->criadoEm() == $cliente->criadoEm(), 'a data original não é reescrita');

secao('A prova de que o Service não conhece HTTP');

// php_strip_whitespace() remove comentários: sem isso o teste leria o próprio
// docblock da classe (que CITA $_POST e Response para dizer que não os usa)
// e acusaria um falso positivo — foi o que aconteceu na primeira versão disto.
$fonte = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Application/Cliente/ClienteService.php');
foreach (['$_POST', '$_GET', '$_SESSION', 'Response::', 'header('] as $proibido) {
    checa("ClienteService não usa {$proibido}", !str_contains($fonte, $proibido), '');
}
nota('É por isso que a API do Módulo 4 reaproveita este Service sem uma linha nova.');

secao('O Controller: fino, só traduz');

$fonteCtrl = (string) file_get_contents(__DIR__ . '/../crm-produto/src/Http/Controllers/ClienteController.php');
checa(
    'ClienteController não monta SQL',
    preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|PDO)\b/i', $fonteCtrl) === 0,
    'nenhuma query no controller',
);
checa(
    'ClienteController não decide duplicidade',
    !str_contains($fonteCtrl, 'buscarPorEmail'),
    'ele captura EmailJaCadastrado, não consulta',
);

secao('Mesma regra, dois pontos de entrada');

// Segunda parte: pelo HTTP. Aqui precisa de banco e autenticação, porque a
// partir do Módulo 5 o painel exige sessão e a API exige Bearer.
bancoDaAula();
$token = tokenDeAula();
logadoNoPainel();

Container::clienteService()->criar(['nome' => 'Ana S. Souza', 'email' => 'ana@exemplo.com']);

$router = Kernel::router();

// Web
$web = $router->resolver(Request::falsa('GET', '/clientes'));
checa('painel web lista pelo Service', str_contains($web->body, 'Ana S. Souza'), "HTTP {$web->status}");

// API — mesmíssimo ClienteService
$api = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Bruno', 'email' => 'ana@exemplo.com']));
checa('API aplica a MESMA regra de e-mail único', $api->status === 409, 'HTTP 409 conflict');

nota('Se a regra vivesse no Controller web, a API teria reescrito — e esquecido um detalhe.');

fecharAula();
