<?php

// PHPIAN · Módulo 2 · Aula 2 — Condicionais
// metadados em aulas.json (2-2)

$nota = 8.5;

if ($nota >= 7) {
    echo "Aprovado";
} elseif ($nota >= 5) {
    echo "Recuperação";
} else {
    echo "Reprovado";
}

$status = match (true) {
    $nota >= 7 => 'Aprovado',
    $nota >= 5 => 'Recuperação',
    default => 'Reprovado',
};