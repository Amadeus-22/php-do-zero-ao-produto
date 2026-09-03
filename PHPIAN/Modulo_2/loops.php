<?php

// PHPIAN · Módulo 2 · Aula 3 — Loops
// metadados em aulas.json (2-3)

for ($i = 1; $i <= 5; $i++) {
    echo "Linha {$i}\n";
}

$frutas = ['maçã', 'banana', 'uva'];
foreach ($frutas as $indice => $fruta) {
    echo "{$indice}: {$fruta}\n";
}

$n = 0;
while ($n < 3) {
    echo $n++;
}