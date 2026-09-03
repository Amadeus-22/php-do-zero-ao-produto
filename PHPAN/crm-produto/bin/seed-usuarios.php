<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;
use App\Support\Container;

/** Seed de desenvolvimento: um usuário por papel. NÃO é migração (isso é schema). */

Config::carregar();

if (Config::string('APP_ENV', 'production') === 'production') {
    fwrite(STDERR, 'Seed não roda em produção.' . PHP_EOL);
    exit(1);
}

$repo = Container::repositorioDeUsuarios();

$usuarios = [
    ['Ana Admin', 'admin@exemplo.com', Papel::ADMIN],
    ['Bruno Vendedor', 'vendedor@exemplo.com', Papel::VENDEDOR],
    ['Clara Leitura', 'leitura@exemplo.com', Papel::LEITURA],
];

foreach ($usuarios as [$nome, $email, $papel]) {
    if ($repo->buscarPorEmail($email) !== null) {
        echo "já existe: {$email}" . PHP_EOL;
        continue;
    }

    $repo->salvar(Usuario::novo($nome, $email, 'senha-de-estudo', $papel));
    echo "criado: {$email} ({$papel->value})" . PHP_EOL;
}
