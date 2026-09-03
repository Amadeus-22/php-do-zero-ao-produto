<?php

// PHPIAN · Módulo 6 · Aula 3 — SELECT e prepared statements
// metadados em aulas.json (6-3)

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM contatos WHERE id = ?');
$stmt->execute([$id]);
$contato = $stmt->fetch();

$stmt = $pdo->query('SELECT id, nome, email FROM contatos ORDER BY nome');
$lista = $stmt->fetchAll();