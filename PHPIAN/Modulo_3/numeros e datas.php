<?php

// PHPIAN · Módulo 3 · Aula 4 — Números e datas
// metadados em aulas.json (3-4)

echo abs(-5);
echo round(3.14159, 2);
echo random_int(1, 100); // criptograficamente seguro

echo date('Y-m-d H:i:s');
$dt = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
echo $dt->format('d/m/Y');
$dt->modify('+7 days');