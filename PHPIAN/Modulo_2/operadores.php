<?php

// PHPIAN · Módulo 2 · Aula 1 — Operadores
// metadados em aulas.json (2-1)

$a=10;
$b=3;

echo $a + $b ; //13
echo $a % $b ; //1
echo $a ** $b ; //1000

$ok = ($a >5) && ($b < 5);
$nome = $_GET['nome'] ?? 'Visitante';