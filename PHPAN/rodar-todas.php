<?php

// PHPAN · roda as 47 aulas e resume
//   php rodar-todas.php          todas
//   php rodar-todas.php 5        só o Módulo 5
//   php rodar-todas.php -v       mostra a saída de cada uma
//
// Os Módulos 5 a 8 precisam do banco:  docker start crm-mysql

declare(strict_types=1);

$modulo = null;
$verboso = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '-v') {
        $verboso = true;
    } elseif (ctype_digit($arg)) {
        $modulo = (int) $arg;
    }
}

$arquivos = glob(__DIR__ . '/Modulo_*/*.php') ?: [];
usort($arquivos, static function (string $a, string $b): int {
    preg_match('#Modulo_(\d+)/(\d+)#', $a, $x);
    preg_match('#Modulo_(\d+)/(\d+)#', $b, $y);
    return [(int) $x[1], (int) $x[2]] <=> [(int) $y[1], (int) $y[2]];
});

$total = 0;
$verdes = 0;
$vermelhas = [];
$assercoes = 0;
$semBanco = 0;
$moduloAtual = null;

foreach ($arquivos as $arquivo) {
    preg_match('#Modulo_(\d+)/(\d+)-([a-z0-9-]+)\.php#', $arquivo, $m);
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

    preg_match('/(\d+) verificações · (\d+) OK · (\d+) falha/u', $texto, $c);
    $assercoes += (int) ($c[1] ?? 0);
    $rotulo = "{$m[2]}-{$m[3]}";

    if ($codigo === 2) {
        // fecharAula() sai com 2 quando o banco não está no ar
        $semBanco++;
        printf("  banco %-40s precisa de: docker start crm-mysql\n", $rotulo);
        continue;
    }

    if ($codigo === 0) {
        $verdes++;
        printf("  ok    %-40s %s\n", $rotulo, ($c[1] ?? '?') . ' verificações');
    } else {
        $vermelhas[] = $rotulo;
        printf("  FALHA %-40s exit %d\n", $rotulo, $codigo);
        foreach (array_filter($saida, static fn ($l) => str_contains($l, '[FALHA]')) as $l) {
            echo '        ', trim($l), "\n";
        }
    }

    if ($verboso) {
        echo $texto, "\n";
    }
}

echo "\n", str_repeat('=', 72), "\n";
printf(
    "  %d aulas · %d ok · %d com falha%s · %d asserções\n",
    $total,
    $verdes,
    count($vermelhas),
    $semBanco > 0 ? " · {$semBanco} aguardando banco" : '',
    $assercoes
);
echo str_repeat('=', 72), "\n";

exit($vermelhas === [] ? 0 : 1);
