<?php

// PHPIAN · Módulo 3 · Aula 2 — Funções de array
// Prática: "Dado um array de preços, use array_filter para ficar só com valores
// acima de 50 e array_sum para totalizar."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 3-2 — filtrar e somar preços');

secao('O que a prática pede');

$precos = [19.90, 349.90, 45.00, 89.50, 50.00, 899.00, 12.30];

$acimaDe50 = array_filter($precos, static fn (float $p): bool => $p > 50);
$total = array_sum($acimaDe50);

nota('todos:     ' . implode(', ', $precos));
nota('acima de 50: ' . implode(', ', $acimaDe50));
nota('total: R$ ' . number_format($total, 2, ',', '.'));

checa('sobraram 3 preços', count($acimaDe50) === 3, implode(', ', $acimaDe50));
checa('o 50.00 exato ficou de fora', !in_array(50.00, $acimaDe50, true), '"acima de 50" é > 50, não >=');
checa('o total soma 1338,40', abs($total - (349.90 + 89.50 + 899.00)) < 0.001, number_format($total, 2, ',', '.'));

secao('A armadilha do array_filter: as chaves são preservadas');

checa('as chaves NÃO viraram 0,1,2,3', array_keys($acimaDe50) === [1, 3, 5],
    'array_filter mantém o índice original');
checa('array_values renumera', array_keys(array_values($acimaDe50)) === [0, 1, 2]);
// Isso morde ao serializar para JSON: com chaves salteadas vira objeto, não lista.
checa('json_encode do filtrado vira OBJETO', str_starts_with(json_encode($acimaDe50), '{'), json_encode($acimaDe50));
checa('com array_values vira LISTA', str_starts_with(json_encode(array_values($acimaDe50)), '['));

secao('array_map, array_reduce — o resto do código da aula');

$nums = [3, 1, 4, 1, 5];
sort($nums);
checa('sort ordena no lugar', $nums === [1, 1, 3, 4, 5]);

$dobro = array_map(static fn (int $n): int => $n * 2, $nums);
checa('array_map dobra cada um', $dobro === [2, 2, 6, 8, 10]);

$pares = array_filter($nums, static fn (int $n): bool => $n % 2 === 0);
checa('array_filter pega os pares', array_values($pares) === [4]);

$soma = array_reduce($nums, static fn (int $acc, int $n): int => $acc + $n, 0);
checa('array_reduce soma tudo', $soma === 14);
checa('array_reduce e array_sum concordam', $soma === array_sum($nums));

secao('in_array e array_key_exists');

checa('in_array(4, ..., true) acha', in_array(4, $nums, true));
// Sem o terceiro parâmetro, "4abc" == 4 seria true em PHP 7; no PHP 8 não é mais,
// mas o hábito de passar strict=true continua certo.
checa('in_array("4", ..., true) NÃO acha (estrito)', !in_array('4', $nums, true), 'tipo diferente');

$usuario = ['nome' => 'Carlos', 'apelido' => null];
checa('array_key_exists acha chave com valor null', array_key_exists('apelido', $usuario));
checa('isset NÃO acha chave com valor null', !isset($usuario['apelido']), 'a diferença entre os dois');

fecharPratica();
