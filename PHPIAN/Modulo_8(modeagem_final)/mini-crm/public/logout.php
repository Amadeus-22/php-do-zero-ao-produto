<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

// Sair é mutação: só por POST e com CSRF, para ninguém deslogar você por um <img>.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

csrf_check();
auth_logout();

sessao_iniciar();
flash_set('ok', 'Sessão encerrada.');
redirect('/login.php');
