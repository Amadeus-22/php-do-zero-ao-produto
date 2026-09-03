<?php

// PHPAN · Módulo 4 · Aula 01 — Recursos, verbos e status HTTP na prática
// metadados em aulas.json · a ideia em 01-recursos-verbos-status.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Cliente\Cliente;
use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;

// A partir do Módulo 5 a API exige Bearer e o painel exige sessão. Esta aula é
// anterior a isso: entra autenticada para continuar exercitando o que ensina.
bancoDaAula();
$token = tokenDeAula();
logadoNoPainel();
$router = Kernel::router();

titulo('Aula 1 — Recursos, verbos e status HTTP');

secao('Recurso é SUBSTANTIVO; a ação vem do verbo');

$mapa = [
    'GET    /api/v1/clientes'      => 'listar',
    'GET    /api/v1/clientes/{id}' => 'mostrar um',
    'POST   /api/v1/clientes'      => 'criar',
    'PUT    /api/v1/clientes/{id}' => 'substituir por completo',
    'DELETE /api/v1/clientes/{id}' => 'remover',
];
foreach ($mapa as $rota => $acao) {
    printf("  %-32s %s\n", $rota, $acao);
}
nota('Nada de /api/v1/listarClientes ou /removerCliente — a ação já está no verbo.');

secao('Cada status comunica o resultado sem abrir o corpo');

$criado = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com']));
checa('POST bem-sucedido devolve 201 Created', $criado->status === 201, 'não 200');

$lista = $router->resolver(Request::comToken('GET', '/api/v1/clientes', $token));
checa('GET de lista devolve 200', $lista->status === 200, '');

$um = $router->resolver(Request::comToken('GET', '/api/v1/clientes/1', $token));
checa('GET de um recurso devolve 200', $um->status === 200, '');

$naoExiste = $router->resolver(Request::comToken('GET', '/api/v1/clientes/999', $token));
checa('recurso inexistente devolve 404', $naoExiste->status === 404, 'not_found');

$invalido = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => '', 'email' => 'x']));
checa('entrada inválida devolve 422, NÃO 500', $invalido->status === 422, '500 é falha SUA, não do usuário');

$duplicado = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Outra', 'email' => 'ana@exemplo.com']));
checa('conflito de estado devolve 409', $duplicado->status === 409, 'e-mail já cadastrado');

$removido = $router->resolver(Request::comToken('DELETE', '/api/v1/clientes/1', $token));
checa('DELETE devolve 204 sem corpo', $removido->status === 204 && $removido->body === 'null', 'No Content');

secao('GET não altera estado — nem se alguém tentar');

$router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Bruno', 'email' => 'bruno@exemplo.com']));
$antes = count(json_decode($router->resolver(Request::comToken('GET', '/api/v1/clientes', $token))->body, true)['data']);

// Não existe rota GET /clientes/{id}/remover — de propósito.
$tentativa = $router->resolver(Request::comToken('GET', '/api/v1/clientes/2/remover', $token));
$depois = count(json_decode($router->resolver(Request::comToken('GET', '/api/v1/clientes', $token))->body, true)['data']);

checa('GET numa "ação" de remoção dá 404', $tentativa->status === 404, 'a rota não existe');
checa('e nada foi removido', $antes === $depois, "{$antes} antes, {$depois} depois");
nota('Link GET que altera estado quebra cache e é disparado por crawler/preload.');

secao('Por que NÃO serializar a entidade direto');

$cliente = Cliente::novo('Ana', 'ana@exemplo.com');
$cru = json_encode(get_object_vars($cliente) ?: ['criadoEm' => $cliente->criadoEm()]);
$viaResource = $router->resolver(Request::comToken('GET', '/api/v1/clientes/2', $token))->body;

checa(
    'o Resource devolve data legível no formato ATOM',
    (bool) preg_match('/"criado_em":"\d{4}-\d{2}-\d{2}T/', $viaResource),
    'DATE_ATOM',
);
nota('json_encode de um DateTimeImmutable viraria {"date":...,"timezone_type":3,...}.');
nota('E qualquer campo interno futuro vazaria no contrato sem ninguém notar.');

fecharAula();
