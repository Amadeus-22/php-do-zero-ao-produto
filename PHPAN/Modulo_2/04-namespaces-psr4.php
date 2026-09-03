<?php

// PHPAN · Módulo 2 · Aula 04 — Namespaces e autoload PSR-4
// metadados em aulas.json · a ideia em 04-namespaces-psr4.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

$raiz = __DIR__ . '/../crm-produto';

titulo('Aula 4 — Namespaces e autoload PSR-4');

secao('A regra: namespace vira caminho, classe vira arquivo');

$composer = json_decode((string) file_get_contents($raiz . '/composer.json'), true);

checa('App\\ mapeia para src/', ($composer['autoload']['psr-4']['App\\'] ?? '') === 'src/', '');
checa('Tests\\ mapeia para tests/', ($composer['autoload-dev']['psr-4']['Tests\\'] ?? '') === 'tests/', 'autoload-dev');

$exemplos = [
    App\Domain\Cliente\Cliente::class => 'src/Domain/Cliente/Cliente.php',
    App\Application\Cliente\CadastrarCliente::class => 'src/Application/Cliente/CadastrarCliente.php',
    App\Infrastructure\Cliente\RepositorioDeClientesPdo::class => 'src/Infrastructure/Cliente/RepositorioDeClientesPdo.php',
    App\Http\Api\V1\ClienteApiController::class => 'src/Http/Api/V1/ClienteApiController.php',
];
foreach ($exemplos as $classe => $esperado) {
    printf("  %-52s %s\n", $classe, $esperado);
    checa('existe: ' . basename($esperado), is_file("{$raiz}/{$esperado}"), '');
}

secao('Conferindo TODAS as classes do projeto');

$divergentes = [];
foreach (glob($raiz . '/src/**/*.php') ?: [] as $arquivo) {
    $codigo = (string) file_get_contents($arquivo);

    if (preg_match('/^namespace\s+([^;]+);/m', $codigo, $ns) !== 1) {
        continue;
    }

    $esperado = $raiz . '/src/' . str_replace('\\', '/', substr($ns[1], strlen('App\\'))) . '/' . basename($arquivo);

    if (realpath($esperado) !== realpath($arquivo)) {
        $divergentes[] = basename($arquivo);
    }
}
$total = count(glob($raiz . '/src/**/*.php') ?: []);
checa('namespace bate com o caminho em todas', $divergentes === [], "{$total} arquivos verificados");

secao('ERRO DE AUTOLOAD reproduzido');

nota('Fatal error: Class "App\\Domain\\Contato\\Contato" not found');
nota('...com o arquivo existindo em src/Domain/Contatos/Contato.php (plural).');

$temp = sys_get_temp_dir() . '/aula-psr4-' . bin2hex(random_bytes(3));
mkdir($temp . '/src/Domain/Contatos', 0o775, true);
file_put_contents(
    $temp . '/src/Domain/Contatos/ContatoErrado.php',
    "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Domain\\Contato;\n\nfinal class ContatoErrado {}\n",
);

$carregou = class_exists('App\Domain\Contato\ContatoErrado');
checa('a classe NÃO é encontrada', !$carregou, 'pasta plural, namespace singular');
nota('O arquivo existe fisicamente. O PHP "não acha" porque o namespace');
nota('DECLARADO não corresponde à pasta. Correção: renomear um dos dois.');

array_map('unlink', glob($temp . '/src/Domain/Contatos/*') ?: []);
foreach (['/src/Domain/Contatos', '/src/Domain', '/src', ''] as $sub) {
    @rmdir($temp . $sub);
}

secao('Uma classe por arquivo');

$multiplas = [];
foreach (glob($raiz . '/src/**/*.php') ?: [] as $arquivo) {
    $codigo = php_strip_whitespace($arquivo);
    $quantas = preg_match_all('/^(final |abstract )?(readonly )?(class|interface|enum) \w+/m', $codigo);

    if ($quantas > 1) {
        $multiplas[] = basename($arquivo) . " ({$quantas})";
    }
}
checa('nenhum arquivo com mais de um tipo', $multiplas === [], $multiplas === [] ? '' : implode(', ', $multiplas));
nota('Não é regra estética: PSR-4 assume uma classe por arquivo, nomeada como ele.');

secao('Estrutura por CAMADA, não por tipo genérico');

foreach (['Domain', 'Application', 'Infrastructure', 'Http', 'Support'] as $camada) {
    printf("  src/%-16s %d arquivos\n", $camada . '/', count(glob($raiz . "/src/{$camada}/**/*.php") ?: []) + count(glob($raiz . "/src/{$camada}/*.php") ?: []));
}

$genericos = array_filter(['Models', 'Helpers', 'Utils', 'Common'], static fn (string $g): bool => is_dir("{$raiz}/src/{$g}"));
checa('sem pasta guarda-tudo (Models, Helpers, Utils)', $genericos === [], $genericos === [] ? '' : implode(', ', $genericos));
nota('Namespace genérico demais é sintoma de falta de organização por camada.');

secao('tests/ espelha src/');

$espelhados = 0;
foreach (glob($raiz . '/tests/**/*Test.php') ?: [] as $teste) {
    $codigo = (string) file_get_contents($teste);

    if (preg_match('/^namespace\s+Tests\\\\/m', $codigo) === 1) {
        $espelhados++;
    }
}
checa('testes usam namespace Tests\\...', $espelhados > 0, "{$espelhados} arquivos");
nota('Espelhar a estrutura facilita achar o teste de qualquer classe.');

secao('composer dump-autoload — quando é preciso');

nota('Classe NOVA que segue a convenção: funciona sozinha, sem comando nenhum.');
nota('Mudou o MAPEAMENTO no composer.json: aí sim, dump-autoload.');

secao('Capitalização: o bug que só aparece em produção');

nota('Em sistema de arquivo case-insensitive (Windows) "cliente/Cliente.php"');
nota('funciona. Em Linux, não. Mantenha a capitalização idêntica sempre.');

fecharAula();
