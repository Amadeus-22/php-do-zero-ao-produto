<?php

// PHPIAN · Módulo 5 · Aula 5 — Composer em 20 minutos
// Prática: "Rode composer init e composer require monolog/monolog. Confirme que
// vendor/autoload.php existe."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 5-5 — Composer');

secao('composer -V');

exec('composer -V 2>&1', $versao, $temComposer);
if ($temComposer !== 0) {
    manual('todo o exercício', 'composer não está instalado nesta máquina');
    checa('composer disponível', false, 'instale em getcomposer.org');
    fecharPratica();
}
checa('composer instalado', $temComposer === 0, $versao[0] ?? '');

$raiz = areaTemporaria('5-5');
$comp = static function (string $args, string $dir): array {
    exec(sprintf(
        'cd %s && COMPOSER_HOME=%s COMPOSER_NO_INTERACTION=1 composer %s 2>&1',
        escapeshellarg($dir),
        escapeshellarg($dir . '/.composer-home'),
        $args
    ), $saida, $codigo);
    return ['saida' => implode("\n", $saida), 'codigo' => $codigo];
};

secao('composer init');

$init = $comp('init --name=phpian/pratica --description="Prática 5-5" --no-interaction', $raiz);
checa('composer init rodou', $init['codigo'] === 0, trim(explode("\n", $init['saida'])[0] ?? ''));
checa('criou composer.json', is_file($raiz . '/composer.json'));

$json = json_decode((string) file_get_contents($raiz . '/composer.json'), true);
checa('composer.json é JSON válido', json_last_error() === JSON_ERROR_NONE);
checa('tem o nome do pacote', ($json['name'] ?? '') === 'phpian/pratica');

secao('composer require monolog/monolog');

$req = $comp('require monolog/monolog --no-interaction --quiet', $raiz);
$temRede = $req['codigo'] === 0;

if (!$temRede) {
    manual('composer require monolog/monolog', 'sem rede: ' . trim(explode("\n", $req['saida'])[0] ?? ''));
    nota('as verificações abaixo usam um pacote local, para provar o mesmo mecanismo');
} else {
    checa('o pacote foi baixado', $temRede);
    checa('monolog entrou em composer.json', str_contains((string) file_get_contents($raiz . '/composer.json'), 'monolog/monolog'));
    checa('composer.lock foi criado', is_file($raiz . '/composer.lock'), 'é o que "composer install" usa em outra máquina');
    checa('vendor/monolog existe', is_dir($raiz . '/vendor/monolog'));
}

secao('vendor/autoload.php — o que a prática manda confirmar');

// Autoload PSR-4 próprio: prova o mecanismo mesmo sem rede, e é o que a aula
// destaca ("carrega classes sem require manual").
mkdir($raiz . '/src', 0777, true);
file_put_contents($raiz . '/src/Saudacao.php', <<<'PHP'
<?php

namespace Phpian\Pratica;

class Saudacao
{
    public function ola(string $nome): string
    {
        return "Olá, {$nome}!";
    }
}
PHP);

$json = json_decode((string) file_get_contents($raiz . '/composer.json'), true);
$json['autoload'] = ['psr-4' => ['Phpian\\Pratica\\' => 'src/']];
file_put_contents($raiz . '/composer.json', json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$dump = $comp('dump-autoload --quiet', $raiz);
checa('composer dump-autoload rodou', $dump['codigo'] === 0, $dump['saida']);
checa('vendor/autoload.php EXISTE', is_file($raiz . '/vendor/autoload.php'), 'o que a prática pede confirmar');

// Usar de verdade, em processo separado
file_put_contents($raiz . '/usar.php', <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$s = new Phpian\Pratica\Saudacao();
echo $s->ola('PHPIAN');
PHP);
$saida = trim((string) shell_exec('php ' . escapeshellarg($raiz . '/usar.php') . ' 2>&1'));
checa('a classe carregou SEM require manual', $saida === 'Olá, PHPIAN!', $saida);
checa('foi o autoload PSR-4 que achou o arquivo', str_contains(
    (string) file_get_contents($raiz . '/vendor/composer/autoload_psr4.php'),
    'Phpian\\\\Pratica'
));

if ($temRede) {
    file_put_contents($raiz . '/log.php', <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$log = new Monolog\Logger('pratica');
$log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/app.log'));
$log->info('funcionou');
echo 'ok';
PHP);
    checa('monolog carrega e escreve log', trim((string) shell_exec('php ' . escapeshellarg($raiz . '/log.php') . ' 2>&1')) === 'ok');
    checa('o log foi gravado', is_file($raiz . '/app.log') && str_contains((string) file_get_contents($raiz . '/app.log'), 'funcionou'));
}

secao('"O que memorizar"');

checa('composer.json lista os pacotes', is_file($raiz . '/composer.json'));
checa('vendor/ é código baixado — não se versiona', true, 'entra no .gitignore (aula 5-4)');
checa('autoload PSR-4 mapeia namespace -> pasta', is_file($raiz . '/vendor/composer/autoload_psr4.php'));

fecharPratica();
