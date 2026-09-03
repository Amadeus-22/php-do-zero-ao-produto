<?php

// PHPIAN · Módulo 7 · Aula 1 — Sessions e cookies
// Prática: "Crie um contador de visitas na sessão e uma página que mostra quantas
// vezes o usuário acessou."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 7-1 — contador de visitas');

secao('A página');

$raiz = areaTemporaria('7-1');
file_put_contents($raiz . '/visitas.php', <<<'PHP'
<?php

declare(strict_types=1);

session_start();

// ??= inicializa só na primeira vez; nas seguintes mantém o que já está lá.
$_SESSION['visitas'] ??= 0;
$_SESSION['visitas']++;
$_SESSION['primeira'] ??= date('c');

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'visitas' => $_SESSION['visitas'],
    'primeira' => $_SESSION['primeira'],
    'sid' => session_id(),
]);
PHP);

$porta = 8791;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$url = "http://127.0.0.1:{$porta}/visitas.php";
$visitar = static function (string $jar) use ($url): array {
    $out = (string) shell_exec(sprintf('curl -s -c %s -b %s %s', escapeshellarg($jar), escapeshellarg($jar), escapeshellarg($url)));
    return json_decode($out, true) ?? [];
};

$jarAna = $raiz . '/ana.txt';

secao('Visitas sucessivas do mesmo usuário');

for ($i = 1; $i <= 5; $i++) {
    $r = $visitar($jarAna);
    checa("visita {$i} conta {$i}", ($r['visitas'] ?? 0) === $i);
}
checa('a primeira visita ficou registrada', !empty($visitar($jarAna)['primeira']));

secao('Outro navegador = outra sessão');

$jarBruno = $raiz . '/bruno.txt';
$b = $visitar($jarBruno);
checa('o segundo usuário começa do 1', ($b['visitas'] ?? 0) === 1, 'a contagem não vaza entre sessões');

$a = $visitar($jarAna);
checa('e o primeiro continua de onde parou', ($a['visitas'] ?? 0) === 7, 'visitas 6 e 7');
checa('os session ids são diferentes', ($a['sid'] ?? '') !== ($b['sid'] ?? ''));

secao('Sem cookie, sem sessão');

$semCookie = json_decode((string) shell_exec('curl -s ' . escapeshellarg($url)), true);
checa('sem enviar cookie, a contagem reinicia', ($semCookie['visitas'] ?? 0) === 1,
    'o cookie é o que liga a requisição à sessão no servidor');

secao('"Sessões guardam no servidor e enviam só um ID"');

$cookies = (string) file_get_contents($jarAna);
checa('o cookie guardado é o PHPSESSID', str_contains($cookies, 'PHPSESSID'));
checa('o cookie NÃO contém a contagem', !str_contains($cookies, 'visitas'),
    'o dado fica no servidor — o navegador só carrega a chave');
checa('o valor do cookie é o session id', str_contains($cookies, (string) ($a['sid'] ?? 'x')));

secao('Cookie x sessão — o que cada um aguenta');

// Cookie: valor visível e editável pelo cliente. Sessão: só o id viaja.
$comCookie = (string) shell_exec(sprintf(
    'curl -s -H %s %s',
    escapeshellarg('Cookie: PHPSESSID=' . ($a['sid'] ?? '') . '; admin=1'),
    escapeshellarg($url)
));
checa('dá para o cliente inventar qualquer cookie', str_contains($comCookie, 'visitas'),
    'por isso "admin=1" em cookie nunca autoriza nada');
nota('a aula está certa: para autenticação, sessão — nunca um cookie com o papel do usuário');

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

secao('Encerrar a sessão, como no código da aula');

$_SESSION['teste'] = 'valor';
checa('o dado está na sessão', ($_SESSION['teste'] ?? null) === 'valor');
$_SESSION = [];
checa('$_SESSION = [] esvazia o array', $_SESSION === []);
$idAntes = session_id();
session_destroy();
checa('session_destroy apaga o arquivo no servidor',
    !is_file(session_save_path() . '/sess_' . $idAntes) || filesize(session_save_path() . '/sess_' . $idAntes) === 0);
checa('a sessão fica inativa', session_status() !== PHP_SESSION_ACTIVE);

fecharPratica();
