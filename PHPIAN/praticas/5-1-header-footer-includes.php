<?php

// PHPIAN · Módulo 5 · Aula 1 — include, require e partials
// Prática: "Separe header/footer em includes e monte 2 páginas que reutilizam o
// mesmo layout."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 5-1 — header/footer em includes');

secao('Os partials e as duas páginas');

$raiz = areaTemporaria('5-1');
mkdir($raiz . '/includes');

file_put_contents($raiz . '/includes/header.php', <<<'PHP'
<?php
// $titulo é definido pela página que inclui este arquivo.
$titulo = $titulo ?? 'PHPIAN';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></title></head>
<body>
<nav><a href="/index.php">Início</a> · <a href="/contato.php">Contato</a></nav>
<main>
PHP);

file_put_contents($raiz . '/includes/footer.php', <<<'PHP'
</main>
<footer>&copy; <?= date('Y') ?> — PHPIAN</footer>
</body>
</html>
PHP);

file_put_contents($raiz . '/index.php', <<<'PHP'
<?php
$titulo = 'Início';
require_once __DIR__ . '/includes/header.php';
?>
<h1>Página inicial</h1>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
PHP);

file_put_contents($raiz . '/contato.php', <<<'PHP'
<?php
$titulo = 'Fale conosco';
require_once __DIR__ . '/includes/header.php';
?>
<h1>Contato</h1>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
PHP);

$porta = 8794;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$pega = static fn (string $p): string => (string) @file_get_contents("http://127.0.0.1:{$porta}/{$p}");
$inicio = $pega('index.php');
$contato = $pega('contato.php');

secao('As duas páginas usam o MESMO layout');

foreach (['<!DOCTYPE html>', '<nav>', '<footer>', '</html>'] as $marca) {
    checa("ambas têm {$marca}", str_contains($inicio, $marca) && str_contains($contato, $marca));
}
checa('o footer traz o ano corrente', str_contains($inicio, '&copy; ' . date('Y')));

secao('E cada uma tem o seu conteúdo');

checa('index tem <h1>Página inicial</h1>', str_contains($inicio, '<h1>Página inicial</h1>'));
checa('contato tem <h1>Contato</h1>', str_contains($contato, '<h1>Contato</h1>'));
checa('o título do <head> muda por página', str_contains($inicio, '<title>Início</title>') && str_contains($contato, '<title>Fale conosco</title>'),
    'a variável $titulo atravessa o include');

secao('include x require — a diferença que a aula define');

$area = $raiz;

// include: aviso, o script segue
file_put_contents($area . '/com-include.php', "<?php\n@include __DIR__ . '/nao-existe.php';\necho 'continuei';\n");
$saida = (string) shell_exec('php ' . escapeshellarg($area . '/com-include.php') . ' 2>&1');
checa('include que falha: o script CONTINUA', str_contains($saida, 'continuei'));

// require: erro fatal, o script morre
file_put_contents($area . '/com-require.php', "<?php\nrequire __DIR__ . '/nao-existe.php';\necho 'não deveria chegar aqui';\n");
$saida2 = (string) shell_exec('php -d display_errors=1 ' . escapeshellarg($area . '/com-require.php') . ' 2>&1');
checa('require que falha: erro FATAL', str_contains($saida2, 'Failed opening required'));
checa('e o script NÃO continua', !str_contains($saida2, 'não deveria chegar aqui'));

secao('*_once evita a inclusão dupla');

file_put_contents($area . '/funcao.php', "<?php\nfunction soDefineUmaVez(): string { return 'ok'; }\n");

file_put_contents($area . '/duas-vezes.php', "<?php\nrequire __DIR__ . '/funcao.php';\nrequire __DIR__ . '/funcao.php';\necho 'chegou';\n");
$dupla = (string) shell_exec('php -d display_errors=1 ' . escapeshellarg($area . '/duas-vezes.php') . ' 2>&1');
checa('require duas vezes: "Cannot redeclare"', str_contains($dupla, 'Cannot redeclare'));

file_put_contents($area . '/once.php', "<?php\nrequire_once __DIR__ . '/funcao.php';\nrequire_once __DIR__ . '/funcao.php';\necho soDefineUmaVez();\n");
checa('require_once duas vezes: sem erro', trim((string) shell_exec('php ' . escapeshellarg($area . '/once.php') . ' 2>&1')) === 'ok');

secao('__DIR__ — por que a aula insiste');

// Caminho relativo depende de ONDE o php foi invocado; __DIR__ é sempre a pasta
// do arquivo que contém a linha.
// './' à frente FORÇA a resolução pelo diretório de trabalho e desliga o fallback
// que o PHP faz para a pasta do script — é aí que o caminho relativo realmente quebra.
file_put_contents($area . '/relativo.php', "<?php\n@include './includes/header.php';\necho isset(\$titulo) ? 'achou' : 'NAO achou';\n");
$deOutraPasta = (string) shell_exec('cd /tmp && php ' . escapeshellarg($area . '/relativo.php') . ' 2>&1');
checa('caminho "./" quebra quando se roda de outra pasta', str_contains($deOutraPasta, 'NAO achou'),
    'depende do diretório de trabalho, não do arquivo');

// O fallback que confunde: sem o "./", o PHP ainda acha, procurando na pasta do script.
file_put_contents($area . '/fallback.php', "<?php\n@include 'includes/header.php';\necho isset(\$titulo) ? 'achou' : 'NAO achou';\n");
checa('sem "./" o PHP procura na pasta do script e acha',
    str_contains((string) shell_exec('cd /tmp && php ' . escapeshellarg($area . '/fallback.php') . ' 2>&1'), 'achou'),
    'o fallback esconde o problema até o dia em que não esconde');

file_put_contents($area . '/absoluto.php', "<?php\n\$titulo='x'; require __DIR__ . '/includes/header.php';\necho 'achou';\n");
$comDir = (string) shell_exec('cd /tmp && php ' . escapeshellarg($area . '/absoluto.php') . ' 2>&1');
checa('com __DIR__ funciona de qualquer lugar', str_contains($comDir, 'achou'));

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

fecharPratica();
