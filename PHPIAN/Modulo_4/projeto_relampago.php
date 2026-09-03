<?php

// PHPIAN · Módulo 4 · Aula 5 — Projeto relâmpago: contato + redirect
// metadados em aulas.json (4-5)

session_start();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $msg = trim($_POST['mensagem'] ?? '');

    $erros = [];
    if ($nome === '' || mb_strlen($nome) < 3) $erros[] = 'Nome inválido';
    if (!$email) $erros[] = 'E-mail inválido';
    if ($msg === '') $erros[] = 'Mensagem obrigatória';

    if ($erros) {
        $_SESSION['flash_erro'] = implode(' · ', $erros);
        header('Location: contato.php');
        exit;
    }

    $linha = date('c') . " | {$nome} | {$email} | {$msg}\n";
    file_put_contents(__DIR__ . '/mensagens.txt', $linha, FILE_APPEND | LOCK_EX);

    $_SESSION['flash_ok'] = 'Mensagem enviada!';
    header('Location: contato.php');
    exit;
}

$ok = $_SESSION['flash_ok'] ?? null;
$erro = $_SESSION['flash_erro'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_erro']);
?>