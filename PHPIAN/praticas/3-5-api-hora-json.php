<?php

// PHPIAN · Módulo 3 · Aula 5 — JSON: dados para o front e APIs
// Prática: "Crie api/hora.php que devolve JSON com hora e timezone de São Paulo.
// Abra no navegador e confira."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 3-5 — api/hora.php');

secao('O endpoint');

$raiz = areaTemporaria('3-5');
mkdir($raiz . '/api');

file_put_contents($raiz . '/api/hora.php', <<<'PHP'
<?php

declare(strict_types=1);

$fuso = new DateTimeZone('America/Sao_Paulo');
$agora = new DateTimeImmutable('now', $fuso);

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'hora' => $agora->format('H:i:s'),
    'data' => $agora->format('Y-m-d'),
    'iso' => $agora->format(DateTimeInterface::ATOM),
    'timezone' => $fuso->getName(),
    'offset' => $agora->format('P'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
PHP);

// "Abra no navegador e confira" — o servidor embutido faz a mesma requisição HTTP.
$porta = 8798;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$ctx = stream_context_create(['http' => ['ignore_errors' => true]]);
$corpo = @file_get_contents("http://127.0.0.1:{$porta}/api/hora.php", false, $ctx);
$cabecalhos = $http_response_header ?? [];

checa('o endpoint respondeu', $corpo !== false);
checa('status 200', str_contains($cabecalhos[0] ?? '', '200'), $cabecalhos[0] ?? '');
checa('Content-Type é application/json',
    (bool) count(array_filter($cabecalhos, static fn ($h) => stripos($h, 'content-type: application/json') === 0)),
    'é isso que faz o fetch() do front tratar como objeto, não texto');

secao('O corpo é JSON válido');

$dados = json_decode((string) $corpo, true);
checa('json_decode aceitou', json_last_error() === JSON_ERROR_NONE, json_last_error_msg());
checa('veio array associativo (true no 2º argumento)', is_array($dados));
nota((string) $corpo);

secao('Os campos que a prática pede');

checa('tem "hora"', isset($dados['hora']));
checa('tem "timezone"', isset($dados['timezone']));
checa('timezone é America/Sao_Paulo', ($dados['timezone'] ?? '') === 'America/Sao_Paulo');
checa('hora no formato HH:MM:SS', (bool) preg_match('/^\d{2}:\d{2}:\d{2}$/', $dados['hora'] ?? ''), $dados['hora'] ?? '');
checa('offset é -03:00 ou -02:00', in_array($dados['offset'] ?? '', ['-03:00', '-02:00'], true),
    ($dados['offset'] ?? '') . ' (Brasil não usa mais horário de verão desde 2019)');

$esperado = (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
checa('a data é a de São Paulo, não a do servidor', ($dados['data'] ?? '') === $esperado);

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

secao('Ler JSON que chega — o outro lado do código da aula');

$lerCorpo = static function (string $raw): array {
    $dados = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['status' => 400, 'corpo' => ['erro' => 'JSON inválido']];
    }
    return ['status' => 200, 'corpo' => $dados];
};

$ok = $lerCorpo('{"nome":"Curso PHPIAN","preco":97.0}');
checa('corpo válido devolve 200', $ok['status'] === 200);
checa('e os dados decodificados', $ok['corpo']['nome'] === 'Curso PHPIAN');

$ruim = $lerCorpo('{isso não é json}');
checa('corpo inválido devolve 400', $ruim['status'] === 400);
checa('com mensagem de erro', $ruim['corpo']['erro'] === 'JSON inválido');

secao('JSON_UNESCAPED_UNICODE — por que a aula usa');

$comAcento = ['nome' => 'Ação Rápida'];
checa('sem a flag, o acento vira \\u', str_contains(json_encode($comAcento), '\\u00e7'), json_encode($comAcento));
checa('com a flag, sai legível', json_encode($comAcento, JSON_UNESCAPED_UNICODE) === '{"nome":"Ação Rápida"}');

fecharPratica();
