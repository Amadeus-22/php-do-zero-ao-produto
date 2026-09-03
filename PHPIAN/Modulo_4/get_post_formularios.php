<?php

// PHPIAN · Módulo 4 · Aula 2 — GET, POST e formulários
// metadados em aulas.json (4-2)

// salvar.php
$nome = $_POST['nome'] ?? '';
if ($nome === '') {
    http_response_code(422);
    echo 'Nome obrigatório';
    exit;
}
echo 'Olá, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');