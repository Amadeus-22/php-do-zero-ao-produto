<?php

// PHPAN · Módulo 3 · Aula 02 — Front Controller e roteamento simples
// metadados em aulas.json · a ideia em 02-front-controller-rotas.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Http\Kernel;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\Container;

// A partir do Módulo 5 a API exige Bearer e o painel exige sessão. Esta aula é
// anterior a isso: entra autenticada para continuar exercitando o que ensina.
bancoDaAula();
$token = tokenDeAula();
logadoNoPainel();

titulo('Aula 2 — Front Controller e roteamento simples');

secao('Request: normaliza a entrada uma vez, no lugar certo');

$req = Request::falsa('get', '/clientes/', ['nome' => 'Ana'], ['pagina' => '2']);
checa('método vira maiúsculo', $req->method === 'GET', $req->method);
checa('barra final some (rtrim)', $req->path === '/clientes', "path={$req->path}");
checa('input() lê do corpo', $req->texto('nome') === 'Ana', 'body tem prioridade');
checa('input() cai na query', $req->texto('pagina') === '2', 'query como fallback');

secao('Response: a saída também é previsível');

checa('html() traz Content-Type', Response::html('<p>oi</p>')->headers['Content-Type'] === 'text/html; charset=utf-8', '');
checa('json() serializa e marca o tipo', Response::json(['a' => 1])->body === '{"a":1}', 'application/json');
checa('redirect() usa Location + 302', Response::redirect('/clientes')->headers['Location'] === '/clientes', 'HTTP 302');

secao('Router: {id} vira ARGUMENTO NOMEADO');

$router = new Router();
$router->get('/clientes/{id}', static fn (Request $r, string $id): Response => Response::json(['id' => $id]));

$resp = $router->resolver(Request::falsa('GET', '/clientes/42'));
checa('o parâmetro chega ao handler pelo nome', $resp->body === '{"id":"42"}', $resp->body);
nota('$params é associativo; ...$params vira argumentos nomeados. O parâmetro do');
nota('handler PRECISA se chamar exatamente como o {placeholder} da rota.');

secao('ARMADILHA 1 — ordem das rotas');

// Rotas na ordem ERRADA: a genérica antes da específica.
$errado = new Router();
$errado->get('/clientes/{id}', static fn (Request $r, string $id): Response => Response::json(['handler' => 'show', 'id' => $id]));
$errado->get('/clientes/novo', static fn (Request $r): Response => Response::json(['handler' => 'novo']));

$caiu = json_decode($errado->resolver(Request::falsa('GET', '/clientes/novo'))->body, true);
checa('na ordem errada, /clientes/novo cai em show', $caiu['handler'] === 'show', 'id="novo" — o bug da aula');

// No projeto a ordem está certa:
$respCerta = Kernel::router()->resolver(Request::falsa('GET', '/clientes/novo'));
checa('no projeto, a específica vem antes', str_contains($respCerta->body, 'Novo cliente'), 'HTTP ' . $respCerta->status);

secao('ARMADILHA 2 — 404 previsível em vez de erro fatal');

$vazio = new Router();
$r404 = $vazio->resolver(Request::falsa('GET', '/qualquer'));
checa('router sem rota nenhuma responde 404', $r404->status === 404, 'e não "call to a member function on null"');

secao('ARMADILHA 3 — barra final inconsistente');

$a = Kernel::router()->resolver(Request::comToken('GET', '/api/v1/clientes', $token))->status;
$b = Kernel::router()->resolver(Request::comToken('GET', '/api/v1/clientes/', $token))->status;
checa('/rota e /rota/ caem na mesma rota', $a === $b, "ambos HTTP {$a}");

secao('ARMADILHA 4 — método errado não vaza para outro handler');

$semPost = Kernel::router()->resolver(Request::falsa('DELETE', '/clientes'));
checa('DELETE numa rota só de GET/POST dá 404', $semPost->status === 404, 'o método faz parte do casamento');

fecharAula();
