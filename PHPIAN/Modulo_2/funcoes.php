<?php

// PHPIAN · Módulo 2 · Aula 4 — Funções
// metadados em aulas.json (2-4)

function formatarPreco(float $valor, string $moeda = 'R$'): string
{
    return $moeda . ' ' . number_format($valor, 2, ',', '.');
}

echo formatarPreco(19.9); // R$ 19,90

function dividir(float $a, float $b): float
{
    if ($b === 0.0) {
        throw new Exception('Divisão por zero');
    }
    return $a / $b;
}