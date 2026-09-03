<?php

// PHPIAN · Módulo 3 · Aula 2 — Funções de array
// metadados em aulas.json (3-2)

$nums = [3, 1, 4, 1, 5];
sort($nums);
$dobro = array_map(fn($n) => $n * 2, $nums);
$pares = array_filter($nums, fn($n) => $n % 2 === 0);
$total = array_reduce($nums, fn($acc, $n) => $acc + $n, 0);

$usuario = ['nome' => 'João', 'idade' => 30];
in_array(4, $nums, true);
array_key_exists('nome', $usuario);