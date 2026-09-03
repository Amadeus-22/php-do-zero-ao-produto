<?php

// PHPAN · Módulo 4 · Aula 05 — Consumir a própria API no painel
// metadados em aulas.json · a ideia em 05-consumir-api-no-painel.md

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

titulo('Aula 5 — Consumir a própria API no painel');

secao('A tela existe e carrega o cliente JS');

$painel = $router->resolver(Request::falsa('GET', '/painel'));
checa('GET /painel responde 200', $painel->status === 200, '');
checa('a página carrega o módulo JS', str_contains($painel->body, 'clientes-api.js'), 'type="module"');
checa('e expõe o csrf-token numa meta', str_contains($painel->body, 'name="csrf-token"'), 'o JS lê daqui');

secao('O que o JS vai receber de fato');

$router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com']));
$resposta = json_decode($router->resolver(Request::comToken('GET', '/api/v1/clientes', $token, [], ['per_page' => 50]))->body, true);

checa('a listagem vem em data[]', isset($resposta['data'][0]['nome']), 'const { data } = await api(...)');
checa('cada item tem id para o data-id do <li>', isset($resposta['data'][0]['id']), '');

$erro = json_decode($router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => '', 'email' => 'x']))->body, true);
checa('o erro tem message pronta para a UI', isset($erro['error']['message']), $erro['error']['message'] ?? '');
nota('É por isso que o JS faz: payload?.error?.message ?? `HTTP ${res.status}`.');

secao('Defesas do cliente JS (lendo o arquivo servido)');

$js = (string) file_get_contents(__DIR__ . '/../crm-produto/public/assets/js/clientes-api.js');

checa(
    'escapeHtml existe — nada de dado cru no innerHTML',
    str_contains($js, 'function escapeHtml'),
    'concatenar HTML com dado do servidor é XSS',
);
checa(
    'e é aplicado ao montar cada <li>',
    str_contains($js, 'escapeHtml(c.nome)') && str_contains($js, 'escapeHtml(c.email)'),
    '',
);
checa(
    "credentials: 'same-origin' — o cookie de sessão vai junto",
    str_contains($js, "credentials: 'same-origin'"),
    'trocar por Bearer no Módulo 5',
);
checa(
    'X-CSRF-TOKEN enviado nas mutações',
    str_contains($js, "'X-CSRF-TOKEN'"),
    'a API ainda usa sessão — precisa de CSRF',
);
checa(
    'res.json() com catch',
    str_contains($js, 'res.json().catch('),
    'um 500 pode devolver HTML de erro do PHP, e json() quebraria',
);
checa(
    'erro vira exceção com status e payload',
    str_contains($js, 'Object.assign(new Error(msg)'),
    'a UI mostra a mensagem, não um "undefined"',
);

secao('A ponte até o Módulo 5');

$decisoes = [
    'Hoje'          => 'sessão do painel + CSRF nas mutações — aceitável, e documentado',
    'Módulo 5'      => 'Authorization: Bearer <token>, sem depender de cookie',
    'O que muda no JS' => 'sai credentials/X-CSRF-TOKEN, entra o header Authorization',
];
foreach ($decisoes as $k => $v) {
    printf("  %-18s %s\n", $k, $v);
}

fecharAula();
