<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

// Porta de entrada: logado vai para o CRM, visitante vai para o login.
redirect(auth_id() > 0 ? '/contatos/index.php' : '/login.php');
