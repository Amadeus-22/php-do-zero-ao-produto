<?php
/** Espera opcionalmente: $titulo (string) */
$titulo = $titulo ?? 'Mini CRM';
$usuario = auth_user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> &middot; Mini CRM</title>
    <link rel="stylesheet" href="<?= e(url('/assets/style.css')) ?>">
</head>
<body>

<header class="topo">
    <div class="container topo-conteudo">
        <a class="marca" href="<?= e(url('/')) ?>">Mini<span>CRM</span></a>

        <?php if ($usuario): ?>
            <nav class="menu">
                <a href="<?= e(url('/contatos/index.php')) ?>">Contatos</a>
                <a href="<?= e(url('/contatos/criar.php')) ?>">Novo contato</a>
            </nav>
            <form class="sair" method="post" action="<?= e(url('/logout.php')) ?>">
                <?= csrf_field() ?>
                <span class="usuario"><?= e($usuario['nome']) ?></span>
                <button type="submit" class="btn btn-link">Sair</button>
            </form>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <?php foreach (flash_all() as $mensagem): ?>
        <div class="alerta alerta-<?= e($mensagem['tipo']) ?>"><?= e($mensagem['mensagem']) ?></div>
    <?php endforeach; ?>
