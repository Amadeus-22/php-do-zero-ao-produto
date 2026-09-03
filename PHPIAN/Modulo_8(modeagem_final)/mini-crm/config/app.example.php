<?php
/**
 * Modelo de configuração. Copie para config/app.php e ajuste.
 * config/app.php está no .gitignore e não vai para o repositório.
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'mini_crm',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // null = detecta sozinho (funciona em subpasta tipo /mini-crm/public).
    // Force uma string se o servidor não colaborar. Ex.: '/mini-crm/public'
    'base_url' => null,

    // true só em localhost. Em produção: false.
    'debug' => true,

    // Permite auto-cadastro em /registrar.php. Desligue depois de criar os usuários.
    'allow_registration' => true,
];
