<?php

// PHPIAN · Módulo 7 · Aula 4 — Segurança web essencial
// metadados em aulas.json (7-4)

session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
// no form: <input type="hidden" name="csrf" value="...">
if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit('CSRF inválido');
}