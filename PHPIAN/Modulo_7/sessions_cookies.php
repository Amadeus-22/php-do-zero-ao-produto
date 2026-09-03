<?php

// PHPIAN · Módulo 7 · Aula 1 — Sessions e cookies
// metadados em aulas.json (7-1)

session_start();
$_SESSION['user_id'] = 42;
$_SESSION['nome'] = 'Ana';

// ler
echo $_SESSION['nome'] ?? 'guest';

// encerrar
$_SESSION = [];
session_destroy();