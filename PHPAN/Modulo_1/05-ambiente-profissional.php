<?php

// PHPAN · Módulo 1 · Aula 05 — Ambiente profissional: PHP 8.3, Composer no fluxo diário
// metadados em aulas.json · a ideia em 05-ambiente-profissional.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

$raiz = __DIR__ . '/../crm-produto';

titulo('Aula 5 — Ambiente profissional');

secao('Passo 1 — versão do PHP');

checa('PHP 8.3 ou superior', PHP_VERSION_ID >= 80300, PHP_VERSION);
$composerJson = json_decode((string) file_get_contents($raiz . '/composer.json'), true);
checa('o projeto exige ^8.3', ($composerJson['require']['php'] ?? '') === '^8.3', 'enum, readonly, property promotion');

secao('Passo 2 — dependências instaladas');

checa('vendor/ existe', is_dir($raiz . '/vendor'), '');
checa('composer.lock versionado', is_file($raiz . '/composer.lock'), 'garante versões idênticas para todos');
checa('vendor/ ignorado no Git', str_contains((string) file_get_contents($raiz . '/.gitignore'), '/vendor/'), 'é gerado, não escrito à mão');

foreach (['phpunit/phpunit' => 'testes', 'phpstan/phpstan' => 'análise estática', 'friendsofphp/php-cs-fixer' => 'estilo'] as $pacote => $para) {
    checa("dev: {$pacote}", isset($composerJson['require-dev'][$pacote]), $para);
}

secao('Autoload PSR-4 — o segundo papel do Composer');

checa('App\\ mapeia para src/', ($composerJson['autoload']['psr-4']['App\\'] ?? '') === 'src/', '');
checa('a classe carrega sem require manual', class_exists(App\Domain\Cliente\Cliente::class), 'use App\\Domain\\Cliente\\Cliente;');
nota('A alternativa seria require_once __DIR__ . "/../../src/Domain/..." em');
nota('cada arquivo. Nome da classe e caminho andam juntos — é o que faz funcionar.');

secao('Passo 3 — os scripts de qualidade');

foreach (['test', 'analyse', 'style:check', 'style:fix', 'quality'] as $script) {
    checa("composer {$script}", isset($composerJson['scripts'][$script]), '');
}
checa('quality roda tudo em sequência', count((array) ($composerJson['scripts']['quality'] ?? [])) >= 3, implode(' -> ', (array) $composerJson['scripts']['quality']));

secao('Passo 4 — QUEBRANDO o PHPStan de propósito');

$arquivo = $raiz . '/src/HealthCheck.php';
$original = (string) file_get_contents($arquivo);

file_put_contents($arquivo, str_replace('public function status(): array', 'public function status(): int', $original));
$saidaQuebrada = (string) shell_exec('cd ' . escapeshellarg($raiz) . ' && composer analyse 2>&1');
file_put_contents($arquivo, $original); // desfaz SEMPRE

checa('o PHPStan pegou o erro', str_contains($saidaQuebrada, 'return.type') || str_contains($saidaQuebrada, 'should return int'), '');
checa('e apontou também quem CONSOME o método', str_contains($saidaQuebrada, 'offsetAccess') || str_contains($saidaQuebrada, 'HealthCheckTest'), 'propagação');
checa('alteração desfeita', (string) file_get_contents($arquivo) === $original, '');

echo "\n  Trecho do que ele disse:\n";
foreach (array_slice(array_filter(explode("\n", $saidaQuebrada), static fn (string $l): bool => str_contains($l, 'return') || str_contains($l, 'offset')), 0, 3) as $linha) {
    echo '    ', trim($linha), "\n";
}
nota('Ele pega DOIS níveis: o erro na classe e a propagação para quem usa —');
nota('antes de rodar, sem precisar de um teste específico para isso.');

secao('Passo 5 — a suíte inteira');

$quality = (string) shell_exec('cd ' . escapeshellarg($raiz) . ' && composer quality 2>&1');

checa('estilo limpo', str_contains($quality, 'Found 0 of'), '');
checa('PHPStan sem erro', str_contains($quality, '[OK] No errors'), 'level 5');
preg_match('/OK \((\d+) tests/', $quality, $m);
checa('testes passando', isset($m[1]), ($m[1] ?? '?') . ' testes');
checa('sem vulnerabilidade em dependência', str_contains($quality, 'No security vulnerability'), 'composer audit');

secao('phpstan.neon');

$neon = (string) file_get_contents($raiz . '/phpstan.neon');
checa('level 5', str_contains($neon, 'level: 5'), 'rigor intermediário (escala 0-9)');
checa('analisa src e tests', str_contains($neon, '- src') && str_contains($neon, '- tests'), '');

secao('O hábito');

echo "  composer install     # uma vez, e quando o lock mudar\n";
echo "  composer test        # durante o desenvolvimento\n";
echo "  composer quality     # PORTÃO — antes de considerar qualquer aula pronta\n";
nota('O hábito só vale se for hábito. Foi assim que os bugs deste projeto');
nota('apareceram: telefone descartado, placeholder duplicado, DDL em transação.');

fecharAula();
