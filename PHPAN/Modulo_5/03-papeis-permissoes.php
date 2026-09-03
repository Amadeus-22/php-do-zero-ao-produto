<?php

// PHPAN · Módulo 5 · Aula 03 — Papéis e permissões: admin, vendedor, leitura
// metadados em aulas.json · a ideia em 03-papeis-permissoes.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Usuario\Gate;
use App\Domain\Usuario\Papel;
use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;

bancoDaAula();
$tokens = tokensPorPapel();
$router = Kernel::router();
$gate = new Gate();

titulo('Aula 3 — Papéis e permissões');

secao('Autenticação x autorização');

nota('Autenticação responde "quem é você" (aulas 1 e 2).');
nota('Autorização responde "o que você PODE fazer" — esta aula.');
nota('"O token é válido" NÃO significa "esse usuário pode fazer isso".');

secao('A matriz vive num lugar só: o Gate');

printf("  %-22s %-8s %-10s %s\n", 'AÇÃO', 'admin', 'vendedor', 'leitura');
foreach (Gate::acoes() as $acao) {
    printf(
        "  %-22s %-8s %-10s %s\n",
        $acao,
        $gate->pode(Papel::ADMIN, $acao) ? 'sim' : '—',
        $gate->pode(Papel::VENDEDOR, $acao) ? 'sim' : '—',
        $gate->pode(Papel::LEITURA, $acao) ? 'sim' : '—',
    );
}

checa('ação desconhecida NEGA por padrão', !$gate->pode(Papel::ADMIN, 'acao.que.nao.existe'), 'lista vazia -> false');
nota('Negar por omissão: uma ação nova nasce fechada até alguém liberar.');

secao('A regra aplicada NA ROTA (é isso que impede o ataque)');

$criar = static fn (string $token): int => $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, [
    'nome' => 'Cliente ' . bin2hex(random_bytes(3)),
    'email' => bin2hex(random_bytes(4)) . '@exemplo.com',
]))->status;

$listar = static fn (string $token): int => $router->resolver(Request::comToken('GET', '/api/v1/clientes', $token))->status;

printf("  %-30s %-8s %-10s %s\n", 'REQUISIÇÃO', 'admin', 'vendedor', 'leitura');
printf("  %-30s %-8d %-10d %d\n", 'GET  /api/v1/clientes', $listar($tokens['admin']), $listar($tokens['vendedor']), $listar($tokens['leitura']));
printf("  %-30s %-8d %-10d %d\n", 'POST /api/v1/clientes', $criar($tokens['admin']), $criar($tokens['vendedor']), $criar($tokens['leitura']));

checa('leitura consegue listar', $listar($tokens['leitura']) === 200, 'HTTP 200');
checa('leitura NÃO consegue criar', $criar($tokens['leitura']) === 403, 'HTTP 403');
checa('vendedor consegue criar', $criar($tokens['vendedor']) === 201, 'HTTP 201');

$alvo = Container::clienteService()->criar(['nome' => 'Alvo', 'email' => 'alvo@exemplo.com'])->id();
$remover = static fn (string $token): int => $router->resolver(
    Request::comToken('DELETE', "/api/v1/clientes/{$alvo}", $token),
)->status;

checa('vendedor NÃO consegue excluir', $remover($tokens['vendedor']) === 403, 'só admin exclui');
checa('leitura NÃO consegue excluir', $remover($tokens['leitura']) === 403, '');
checa('admin consegue excluir', $remover($tokens['admin']) === 204, 'HTTP 204');

secao('401 e 403 dizem coisas diferentes');

$semToken = $router->resolver(Request::falsa('POST', '/api/v1/clientes', ['nome' => 'X', 'email' => 'x@exemplo.com']));
$comTokenSemPermissao = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $tokens['leitura'], [
    'nome' => 'X',
    'email' => 'x2@exemplo.com',
]));

checa('sem token -> 401 unauthorized', $semToken->status === 401, 'não sei quem você é');
checa('com token, sem permissão -> 403 forbidden', $comTokenSemPermissao->status === 403, 'sei quem é, e não pode');
checa(
    'o code do erro também diferencia',
    json_decode($comTokenSemPermissao->body, true)['error']['code'] === 'forbidden',
    'o front trata por code',
);

secao('ARMADILHA — esconder o botão não é autorização');

nota('Esconder o botão no HTML é UX. Qualquer um com curl chama a rota direto.');
nota('A prova está acima: o 403 veio do SERVIDOR, com requisição crua, sem tela.');

secao('E o painel web? Mesma regra, outro middleware');

$_SESSION = ['usuario_id' => 1, 'papel' => 'vendedor', 'criado_em' => time()];
$painel = $router->resolver(Request::falsa('POST', '/clientes/1/remover', ['_token' => 'x']));
checa('vendedor logado no painel também leva 403', $painel->status === 403, 'AdminMiddleware');
$_SESSION = [];

nota('É comum proteger a tela e esquecer que a mesma ação tem rota JSON equivalente.');
nota('Aqui as duas passam pela mesma matriz de papéis.');

fecharAula();
