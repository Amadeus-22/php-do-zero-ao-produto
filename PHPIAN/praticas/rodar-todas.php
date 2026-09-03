<?php

// PHPIAN · roda as 40 práticas e resume
//   php praticas/rodar-todas.php          todas
//   php praticas/rodar-todas.php 6        só o Módulo 6
//   php praticas/rodar-todas.php -v       mostra a saída de cada uma

declare(strict_types=1);

$dir = __DIR__;
$modulo = null;
$verboso = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '-v') {
        $verboso = true;
    } elseif (ctype_digit($arg)) {
        $modulo = (int) $arg;
    }
}

$arquivos = glob($dir . '/[0-9]*.php') ?: [];
usort($arquivos, static function (string $a, string $b): int {
    preg_match('/(\d+)-(\d+)/', basename($a), $x);
    preg_match('/(\d+)-(\d+)/', basename($b), $y);
    return [(int) $x[1], (int) $x[2]] <=> [(int) $y[1], (int) $y[2]];
});

$total = 0;
$verdes = 0;
$vermelhas = [];
$assercoes = 0;
$manuais = 0;
$moduloAtual = null;

foreach ($arquivos as $arquivo) {
    preg_match('/^(\d+)-(\d+)/', basename($arquivo), $m);
    $mod = (int) $m[1];
    if ($modulo !== null && $mod !== $modulo) {
        continue;
    }
    if ($mod !== $moduloAtual) {
        $moduloAtual = $mod;
        echo "\nMódulo {$mod}\n";
    }

    $saida = [];
    exec('php ' . escapeshellarg($arquivo) . ' 2>&1', $saida, $codigo);
    $texto = implode("\n", $saida);
    $total++;

    // "N verificações · N OK · N falha(s) · N manual(is)"
    preg_match('/(\d+) verificações · (\d+) OK · (\d+) falha/u', $texto, $c);
    $assercoes += (int) ($c[1] ?? 0);
    $manuais += substr_count($texto, '[MANUAL]');

    if ($codigo === 0) {
        $verdes++;
        printf("  ok    %-38s %s\n", basename($arquivo, '.php'), ($c[1] ?? '?') . ' verificações');
    } else {
        $vermelhas[] = basename($arquivo);
        printf("  FALHA %-38s exit %d\n", basename($arquivo, '.php'), $codigo);
        foreach (array_filter($saida, static fn ($l) => str_contains($l, '[FALHA]')) as $l) {
            echo '        ', trim($l), "\n";
        }
    }

    if ($verboso) {
        echo $texto, "\n";
    }
}

echo "\n", str_repeat('=', 72), "\n";
printf("  %d práticas · %d ok · %d com falha · %d asserções · %d passo(s) manual(is)\n",
    $total, $verdes, count($vermelhas), $assercoes, $manuais);
echo str_repeat('=', 72), "\n";

exit($vermelhas === [] ? 0 : 1);
