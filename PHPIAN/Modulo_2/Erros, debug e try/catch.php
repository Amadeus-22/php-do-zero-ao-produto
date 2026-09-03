<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

<?php
function dividir(float $a, float $b): float
{
    if ($b === 0.0) {
        throw new InvalidArgumentException('Divisão por zero');
    }
    return $a / $b;
}

try {
    echo dividir(10, 0);
} catch (InvalidArgumentException $e) {
    echo 'Erro: ' . $e->getMessage();
}