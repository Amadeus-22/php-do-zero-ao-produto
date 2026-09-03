<?php

// PHPIAN · Módulo 3 · Aula 5 — JSON: dados para o front e APIs
// metadados em aulas.json (3-5)

$produto = ['nome' => 'Curso PHPIAN', 'preco' => 97.0, 'ativo' => true];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($produto, JSON_UNESCAPED_UNICODE);

// ler JSON recebido (ex.: fetch/AJAX)
$raw = file_get_contents('php://input');
$dados = json_decode($raw, true); // true = array associativo
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['erro' => 'JSON inválido']);
    exit;
}