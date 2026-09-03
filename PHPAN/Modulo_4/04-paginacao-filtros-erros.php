<?php

// PHPAN · Módulo 4 · Aula 04 — Paginação, filtros e erros padronizados
// metadados em aulas.json · a ideia em 04-paginacao-filtros-erros.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;

// A partir do Módulo 5 a API exige Bearer e o painel exige sessão. Esta aula é
// anterior a isso: entra autenticada para continuar exercitando o que ensina.
bancoDaAula();
$token = tokenDeAula();
logadoNoPainel();
$router = Kernel::router();

// 25 clientes para a paginação ter o que paginar
foreach (range(1, 25) as $i) {
    $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, [
        'nome' => sprintf('Cliente %02d %s', $i, $i % 3 === 0 ? 'Silva' : 'Souza'),
        'email' => "cliente{$i}@exemplo.com",
    ]));
}

$lista = static fn (array $query): array
    => json_decode($router->resolver(Request::comToken('GET', '/api/v1/clientes', $token, [], $query))->body, true);

titulo('Aula 4 — Paginação, filtros e erros padronizados');

secao('meta: o cliente sabe onde está sem adivinhar');

$p1 = $lista([]);
checa('per_page padrão é 20', $p1['meta']['per_page'] === 20, '');
checa('total conta o conjunto inteiro', $p1['meta']['total'] === 25, "total={$p1['meta']['total']}");
checa('total_pages calculado por ceil', $p1['meta']['total_pages'] === 2, '25/20 -> 2');
checa('a primeira página traz 20 itens', count($p1['data']) === 20, '');

$p2 = $lista(['page' => 2]);
checa('a segunda página traz os 5 restantes', count($p2['data']) === 5, '');
checa('e não repete item da página 1', $p1['data'][0]['id'] !== $p2['data'][0]['id'], 'offset correto');

secao('ARMADILHA — page 0 e per_page gigante');

$zero = $lista(['page' => 0]);
checa('page=0 vira page=1', $zero['meta']['page'] === 1, 'max(1, ...)');

$negativo = $lista(['page' => -5]);
checa('page negativa também vira 1', $negativo['meta']['page'] === 1, '');

$gigante = $lista(['per_page' => 999999]);
checa('per_page=999999 é limitado a 100', $gigante['meta']['per_page'] === 100, 'teto obrigatório');
nota('Sem teto, um cliente derruba o servidor pedindo a base inteira numa requisição.');

$texto = $lista(['per_page' => 'abc']);
checa('per_page inválido cai no mínimo seguro', $texto['meta']['per_page'] >= 1, "per_page={$texto['meta']['per_page']}");

secao('Filtro q: busca em nome e e-mail');

$silva = $lista(['q' => 'Silva']);
checa('q filtra o conjunto', $silva['meta']['total'] === 8, "encontrou {$silva['meta']['total']} 'Silva'");
checa('e o meta reflete o filtro, não o total geral', $silva['meta']['total'] < 25, 'total é do resultado filtrado');

$porEmail = $lista(['q' => 'cliente7@']);
checa('q também bate no e-mail', $porEmail['meta']['total'] === 1, '');

$nada = $lista(['q' => 'zzzz']);
checa('sem resultado devolve data vazio, não erro', $nada['data'] === [] && $nada['meta']['total'] === 0, 'HTTP 200');

secao('Erros no MESMO envelope em todos os endpoints');

$casos = [
    'validação (422)' => ['POST', '/api/v1/clientes', ['nome' => '', 'email' => 'x'], 422, 'validation_failed'],
    'não encontrado (404)' => ['GET', '/api/v1/clientes/9999', [], 404, 'not_found'],
    'conflito (409)' => ['POST', '/api/v1/clientes', ['nome' => 'X', 'email' => 'cliente1@exemplo.com'], 409, 'conflict'],
];
foreach ($casos as $rotulo => [$metodo, $rota, $corpo, $status, $code]) {
    $r = $router->resolver(Request::comToken($metodo, $rota, $token, $corpo));
    $j = json_decode($r->body, true);
    checa(
        "{$rotulo} usa o envelope {error}",
        $r->status === $status && ($j['error']['code'] ?? '') === $code,
        "HTTP {$r->status} · code={$j['error']['code']}",
    );
}
nota('Códigos do curso: validation_failed, not_found, unauthorized, forbidden,');
nota('conflict, rate_limited, server_error. O front trata por code, não por texto.');

secao('NOTA sobre onde a paginação acontece');

nota('Este teste roda com o duplo em memória (array já carregado).');
nota('No RepositorioDeClientesPdo a paginação é WHERE + LIMIT/OFFSET no SQL —');
nota('paginar depois de um SELECT * é a armadilha que a aula aponta.');

fecharAula();
