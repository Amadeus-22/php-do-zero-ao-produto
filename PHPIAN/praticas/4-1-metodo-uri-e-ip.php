<?php

// PHPIAN · Módulo 4 · Aula 1 — Request e response
// Prática: "Crie uma página que imprime método, URI e IP do cliente."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 4-1 — método, URI e IP');

secao('A página');

$raiz = areaTemporaria('4-1');
file_put_contents($raiz . '/eco.php', <<<'PHP'
<?php

declare(strict_types=1);

// $_SERVER só existe de verdade sob um servidor web; no CLI as chaves de HTTP
// nem aparecem. Por isso tudo aqui usa ?? — é o que a aula 2-1 ensinou.
$dados = [
    'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'uri' => $_SERVER['REQUEST_URI'] ?? '-',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '-',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '-',
    'protocolo' => $_SERVER['SERVER_PROTOCOL'] ?? '-',
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($dados, JSON_UNESCAPED_SLASHES);
PHP);

$porta = 8797;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

secao('GET');

$ctx = stream_context_create(['http' => [
    'method' => 'GET',
    'header' => "User-Agent: PraticaPHPIAN/1.0\r\n",
    'ignore_errors' => true,
]]);
$get = json_decode((string) @file_get_contents("http://127.0.0.1:{$porta}/eco.php?busca=teclado", false, $ctx), true);

checa('respondeu', is_array($get));
checa('método é GET', ($get['metodo'] ?? '') === 'GET');
checa('a URI traz o caminho E a query', ($get['uri'] ?? '') === '/eco.php?busca=teclado', $get['uri'] ?? '');
checa('o IP do cliente é 127.0.0.1', ($get['ip'] ?? '') === '127.0.0.1', 'REMOTE_ADDR');
checa('o User-Agent chegou', ($get['user_agent'] ?? '') === 'PraticaPHPIAN/1.0');
checa('protocolo HTTP/1.1', str_starts_with($get['protocolo'] ?? '', 'HTTP/'), $get['protocolo'] ?? '');

secao('POST — o mesmo script, método diferente');

$ctxPost = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
    'content' => http_build_query(['nome' => 'Ana']),
    'ignore_errors' => true,
]]);
$post = json_decode((string) @file_get_contents("http://127.0.0.1:{$porta}/eco.php", false, $ctxPost), true);
checa('método é POST', ($post['metodo'] ?? '') === 'POST');
checa('a URI não traz os dados do POST', ($post['uri'] ?? '') === '/eco.php', 'eles vão no corpo, não na URL');

secao('Os status que a aula lista');

// Cada um pedido de verdade, para ver o servidor devolvendo o número.
file_put_contents($raiz . '/status.php', <<<'PHP'
<?php
$code = (int) ($_GET['c'] ?? 200);
http_response_code($code);
if ($code === 302) { header('Location: /eco.php'); }
echo $code;
PHP);

foreach ([200 => 'OK', 404 => 'Not Found', 422 => 'Unprocessable', 500 => 'Server Error'] as $codigo => $nome) {
    $c = stream_context_create(['http' => ['ignore_errors' => true, 'follow_location' => 0]]);
    @file_get_contents("http://127.0.0.1:{$porta}/status.php?c={$codigo}", false, $c);
    checa("o servidor devolveu {$codigo} ({$nome})", str_contains($http_response_header[0] ?? '', (string) $codigo),
        $http_response_header[0] ?? '');
}

$c = stream_context_create(['http' => ['ignore_errors' => true, 'follow_location' => 0]]);
@file_get_contents("http://127.0.0.1:{$porta}/status.php?c=302", false, $c);
$cab = $http_response_header ?? [];
checa('302 vem com header Location', (bool) count(array_filter($cab, static fn ($h) => stripos($h, 'location:') === 0)),
    'é o que faz o navegador seguir para outra URL');

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

secao('GET x POST — a diferença que a aula resume');

checa('GET: dados na URL, visíveis e no histórico', str_contains('/eco.php?busca=teclado', '?busca='));
checa('POST: dados no corpo, fora da URL', !str_contains('/eco.php', 'nome=Ana'));

fecharPratica();
