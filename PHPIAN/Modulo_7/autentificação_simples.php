<?php

// PHPIAN · Módulo 7 · Aula 3 — Autenticação simples
// metadados em aulas.json (7-3)

// ...existing code...
$requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
$email = filter_input(INPUT_POST, 'email');
$senha = filter_input(INPUT_POST, 'senha');

if ($requestMethod === 'POST' && $email !== null && $senha !== null) {
    $stmt = $pdo->prepare('SELECT id, nome, senha_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        header('Location: /dashboard.php');
        exit;
    }
}
// ...existing code...