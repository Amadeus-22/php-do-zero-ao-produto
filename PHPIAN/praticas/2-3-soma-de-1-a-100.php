<?php

// PHPIAN · Módulo 2 · Aula 3 — Loops
// Prática: "Some todos os números de 1 a 100 com um loop e exiba o total (5050)."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 2-3 — soma de 1 a 100');

secao('Com for');

$total = 0;
for ($i = 1; $i <= 100; $i++) {
    $total += $i;
}
nota("total = {$total}");
checa('for soma 5050', $total === 5050);

secao('Com foreach sobre range()');

$totalForeach = 0;
foreach (range(1, 100) as $n) {
    $totalForeach += $n;
}
checa('foreach chega no mesmo 5050', $totalForeach === 5050);

secao('Com while');

$totalWhile = 0;
$n = 1;
while ($n <= 100) {
    $totalWhile += $n++;
}
checa('while chega no mesmo 5050', $totalWhile === 5050);

secao('Conferindo por fora');

// Gauss: n*(n+1)/2. Se a fórmula bate com o loop, o loop está certo.
checa('bate com a fórmula de Gauss n(n+1)/2', $total === (100 * 101) / 2, '100 × 101 / 2 = 5050');
checa('bate com array_sum', $total === array_sum(range(1, 100)));

secao('break e continue');

// break sai do loop
$ateCem = 0;
foreach (range(1, 1000) as $n) {
    if ($n > 100) {
        break;
    }
    $ateCem += $n;
}
checa('break parou em 100', $ateCem === 5050);

// continue pula a iteração
$somaPares = 0;
for ($i = 1; $i <= 100; $i++) {
    if ($i % 2 !== 0) {
        continue;
    }
    $somaPares += $i;
}
checa('continue somou só os pares', $somaPares === 2550, '2+4+...+100 = 2550');
checa('pares + ímpares = total', $somaPares + (5050 - $somaPares) === 5050);

secao('O foreach com chave, como no código da aula');

$frutas = ['maçã', 'banana', 'uva'];
$saida = [];
foreach ($frutas as $indice => $fruta) {
    $saida[] = "{$indice}: {$fruta}";
}
checa('índices vêm de 0', $saida[0] === '0: maçã');
checa('percorreu as três', count($saida) === 3, implode(' | ', $saida));

fecharPratica();
