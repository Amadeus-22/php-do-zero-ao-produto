<?php

// PHPIAN · Módulo 4 · Aula 3 — Validação e sanitização
// metadados em aulas.json (4-3)

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$idade = filter_input(INPUT_POST, 'idade', FILTER_VALIDATE_INT);

$erros = [];
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'E-mail inválido';
}
if ($idade === false || $idade < 18) {
    $erros[] = 'Idade mínima: 18';
}

if ($erros) {
    // reexibir formulário com mensagens
}