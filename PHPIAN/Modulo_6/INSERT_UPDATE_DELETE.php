<?php

// PHPIAN · Módulo 6 · Aula 4 — INSERT, UPDATE e DELETE
// metadados em aulas.json (6-4)

//colocando valores para teste
$nome = 'barbosa';
$email = 'barbosa@gmail.com';
$telefone = '2199999999';
$id = 'pedro';
//inserindo valores no banco de dados
$stmt = $pdo->prepare(
    'INSERT INTO contatos (nome, email, telefone) VALUES (?, ?, ?)'
);
$stmt->execute([$nome, $email, $telefone]);

$stmt = $pdo->prepare('UPDATE contatos SET telefone = ? WHERE id = ?');
$stmt->execute([$telefone, $id]);

$stmt = $pdo->prepare('DELETE FROM contatos WHERE id = ?');
$stmt->execute([$id]);