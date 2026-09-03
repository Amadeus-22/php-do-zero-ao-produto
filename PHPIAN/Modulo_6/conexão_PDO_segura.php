<?php

// PHPIAN · Módulo 6 · Aula 2 — Conexão PDO segura
// metadados em aulas.json (6-2)

$dsn = 'mysql:host=127.0.0.1;dbname=phpian;charset=utf8mb4';
$user = 'root';
$pass = '';

$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);