<?php

// PHPAN · Módulo 7 · Aula 01 — .env, secrets e config por ambiente
// metadados em aulas.json · a ideia em 01-env-secrets-config.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Config\Config;

$raiz = __DIR__ . '/../crm-produto';
Config::carregar();

titulo('Aula 1 — .env, secrets e config por ambiente');

secao('Regra de ouro');

nota('Código NÃO muda entre ambientes; configuração muda.');
nota('Se você precisa editar uma linha de PHP para rodar em staging, não tem');
nota('configuração — tem gambiarra.');

secao('Três categorias que se confundem');

printf("  %-22s %s\n", 'Config', 'varia por ambiente, não é segredo (APP_ENV, APP_URL)');
printf("  %-22s %s\n", 'Secret', 'varia por ambiente E é segredo (DB_PASSWORD, API_KEY)');
printf("  %-22s %s\n", 'Constante de domínio', 'não varia — pode viver no código (enums, regras)');

secao('Leitura centralizada');

checa('APP_ENV lido pela Config', Config::string('APP_ENV', 'production') !== '', Config::string('APP_ENV'));
checa('DB_PORT convertido para int', Config::int('DB_PORT', 3306) === 3307, 'porta do container');
checa('valor ausente usa o padrão', Config::string('NAO_EXISTE', 'padrao') === 'padrao', '');

checaExcecao(
    'config obrigatória ausente QUEBRA NO BOOT',
    RuntimeException::class,
    static fn () => Config::string('VARIAVEL_QUE_NAO_EXISTE'),
);
nota('Melhor estourar no boot com mensagem clara do que na primeira query,');
nota('40 minutos depois, com erro que não diz o que faltou.');

secao('O BUG CLÁSSICO: "false" é truthy em PHP');

putenv('TESTE_DEBUG=false');
$_ENV['TESTE_DEBUG'] = 'false';

$ingenuo = (bool) $_ENV['TESTE_DEBUG'];          // string não-vazia -> true
$correto = Config::bool('TESTE_DEBUG', true);    // FILTER_VALIDATE_BOOLEAN

checa('cast direto de "false" dá TRUE (o bug)', $ingenuo === true, 'string não-vazia é truthy');
checa('Config::bool() entende "false"', $correto === false, 'filter_var(FILTER_VALIDATE_BOOLEAN)');
nota('É o clássico "desliguei o APP_DEBUG e continua ligado".');
nota('getenv() SEMPRE devolve string — nunca booleano.');

foreach (['1' => true, 'true' => true, 'on' => true, '0' => false, 'false' => false, 'off' => false] as $valor => $esperado) {
    $_ENV['TESTE_V'] = $valor;
    printf("  Config::bool('%-5s') = %s\n", $valor, var_export(Config::bool('TESTE_V'), true));
}

secao('Nenhum getenv() solto fora da Config');

$soltos = [];
foreach (glob($raiz . '/src/**/*.php') ?: [] as $arquivo) {
    if (str_contains($arquivo, 'Config.php')) {
        continue;
    }

    if (preg_match('/getenv\(|\$_ENV\[/', php_strip_whitespace($arquivo)) === 1) {
        $soltos[] = basename($arquivo);
    }
}
checa('leitura de ambiente só na Config', $soltos === [], $soltos === [] ? 'nenhum vazamento' : implode(', ', $soltos));

secao('.env fora do Git, .env.example dentro');

$gitignore = (string) file_get_contents($raiz . '/.gitignore');

checa('.env ignorado', str_contains($gitignore, '.env'), '');
checa('.env.example NÃO ignorado', str_contains($gitignore, '!.env.example'), '');

$chaves = static function (string $arquivo): array {
    preg_match_all('/^([A-Z_]+)=/m', (string) file_get_contents($arquivo), $m);
    sort($m[1]);

    return $m[1];
};

$noEnv = $chaves($raiz . '/.env');
$noExemplo = $chaves($raiz . '/.env.example');
$faltando = array_diff($noEnv, $noExemplo);
$sobrando = array_diff($noExemplo, $noEnv);

checa('exemplo tem as mesmas chaves do .env', $faltando === [], $faltando === [] ? count($noEnv) . ' chaves' : 'faltam: ' . implode(', ', $faltando));
checa('e não tem chave a mais', $sobrando === [], $sobrando === [] ? '' : 'sobram: ' . implode(', ', $sobrando));
nota('Divergência entre os dois é a causa nº 1 de "funciona na minha máquina".');

secao('O exemplo não pode conter segredo de verdade');

$exemplo = (string) file_get_contents($raiz . '/.env.example');
$envReal = (string) file_get_contents($raiz . '/.env');

preg_match('/DB_PASSWORD=(.*)/', $envReal, $real);
checa('a senha real NÃO está no .env.example', !str_contains($exemplo, trim($real[1] ?? 'xxx')) || trim($real[1] ?? '') === '', '');

secao('Commitou o .env uma vez?');

nota('O segredo está no histórico do Git PARA SEMPRE. git rm não resolve —');
nota('rotacionar a credencial é obrigatório.');

fecharAula();
