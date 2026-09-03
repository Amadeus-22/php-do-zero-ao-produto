<?php

// PHPIAN · Módulo 5 · Aula 2 — Estrutura de pastas e config
// Prática: "Crie a estrutura acima e um config/app.php que retorna array com
// nome, debug e timezone."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 5-2 — estrutura de pastas e config');

secao('A estrutura da aula');

$raiz = areaTemporaria('5-2');
$pastas = ['public', 'public/assets', 'src', 'templates', 'config', 'storage', 'storage/uploads'];
foreach ($pastas as $p) {
    mkdir($raiz . '/' . $p, 0777, true);
}
file_put_contents($raiz . '/public/index.php', "<?php\n\$config = require __DIR__ . '/../config/app.php';\necho \$config['nome'];\n");

foreach ($pastas as $p) {
    checa("existe {$p}/", is_dir($raiz . '/' . $p));
}
checa('public/index.php é o ponto de entrada', is_file($raiz . '/public/index.php'));

secao('O config/app.php');

file_put_contents($raiz . '/config/app.php', <<<'PHP'
<?php

declare(strict_types=1);

// Retorna array em vez de definir constantes: dá para carregar em teste, sobrepor
// por ambiente e passar adiante como valor.
return [
    'nome' => 'Mini CRM',
    'debug' => (getenv('APP_DEBUG') ?: 'false') === 'true',
    'timezone' => 'America/Sao_Paulo',
];
PHP);

$config = require $raiz . '/config/app.php';

checa('o arquivo retorna um array', is_array($config));
checa('tem as 3 chaves pedidas', array_keys($config) === ['nome', 'debug', 'timezone']);
checa('nome é string', is_string($config['nome']), $config['nome']);
checa('debug é bool', is_bool($config['debug']), var_export($config['debug'], true));
checa('timezone é America/Sao_Paulo', $config['timezone'] === 'America/Sao_Paulo');
checa('o timezone é válido para o PHP', in_array($config['timezone'], DateTimeZone::listIdentifiers(), true));

secao('debug vem do ambiente, não fica cravado');

putenv('APP_DEBUG=true');
$comDebug = require $raiz . '/config/app.php';
checa('APP_DEBUG=true liga o debug', $comDebug['debug'] === true);
putenv('APP_DEBUG=false');
$semDebug = require $raiz . '/config/app.php';
checa('APP_DEBUG=false desliga', $semDebug['debug'] === false);
putenv('APP_DEBUG');
$padrao = require $raiz . '/config/app.php';
checa('sem a variável, o padrão é desligado', $padrao['debug'] === false, 'seguro por omissão');

secao('.env fora do Git, .env.example dentro');

file_put_contents($raiz . '/.env', "DB_HOST=127.0.0.1\nDB_PASS=senha-real-de-producao\n");
file_put_contents($raiz . '/.env.example', "DB_HOST=127.0.0.1\nDB_PASS=\n");
file_put_contents($raiz . '/.gitignore', "/vendor/\n.env\n/storage/uploads/*\n!/storage/uploads/.gitkeep\n");

$gitignore = (string) file_get_contents($raiz . '/.gitignore');
checa('.gitignore ignora o .env', str_contains($gitignore, ".env\n"));
checa('.env.example existe e vai para o Git', is_file($raiz . '/.env.example') && !str_contains($gitignore, '.env.example'));
checa('o .example NÃO tem a senha', !str_contains((string) file_get_contents($raiz . '/.env.example'), 'senha-real'));
checa('o .env real TEM a senha', str_contains((string) file_get_contents($raiz . '/.env'), 'senha-real'));

secao('Por que public/ é o DocumentRoot');

// Com o DocumentRoot em public/, nada fora dela é alcançável por URL — nem o
// .env, nem src/, nem config/. É a razão de a estrutura ter essa forma.
$forasDaRaiz = ['config/app.php', 'src', '.env'];
foreach ($forasDaRaiz as $f) {
    checa("{$f} está FORA de public/", !str_starts_with($f, 'public/'), 'inalcançável por URL');
}

$porta = 8793;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz . '/public')),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$ctx = stream_context_create(['http' => ['ignore_errors' => true]]);
$home = (string) @file_get_contents("http://127.0.0.1:{$porta}/index.php", false, $ctx);
checa('index.php roda e lê o config', str_contains($home, 'Mini CRM'));

// O caminho vai cru no socket: file_get_contents normalizaria "/../.env" para "/"
// e o teste passaria por engano, servindo o index.
$cru = static function (string $caminho) use ($porta): string {
    $s = @fsockopen('127.0.0.1', $porta, $e, $m, 3);
    if ($s === false) {
        return '';
    }
    fwrite($s, "GET {$caminho} HTTP/1.0\r\nHost: 127.0.0.1\r\n\r\n");
    $r = (string) stream_get_contents($s);
    fclose($s);
    return $r;
};
// O `php -S` responde 200 servindo o index.php para QUALQUER caminho que não
// exista — então o status não prova nada. O que prova é o conteúdo: o segredo
// não pode aparecer na resposta.
foreach (['/.env', '/../.env', '/../config/app.php', '/config/app.php'] as $tentativa) {
    checa(
        sprintf('%-22s não devolve o segredo', $tentativa),
        !str_contains($cru($tentativa), 'senha-real-de-producao'),
        'está fora do DocumentRoot'
    );
}
checa('e o que responde é o index, não o arquivo', str_contains($cru('/.env'), 'Mini CRM'),
    'fallback do servidor embutido');

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

fecharPratica();
