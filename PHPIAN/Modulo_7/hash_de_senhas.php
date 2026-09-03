<?php

// PHPIAN · Módulo 7 · Aula 2 — Hash de senhas
// metadados em aulas.json (7-2)

$senhaPura = ''; // define a senha pura
$hash = password_hash($senhaPura, PASSWORD_DEFAULT);

$senhaDigitada = ''; // define a senha digitada
if (password_verify($senhaDigitada, $hash)) {
    // login ok
}

if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
    // atualizar hash no banco
}