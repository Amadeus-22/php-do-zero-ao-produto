<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use App\Support\Container;

/** Cron diário: sem isto a tabela de tentativas de login cresce para sempre. */

Config::carregar();

echo Container::rateLimiter()->limparAntigos(), " registro(s) antigo(s) removido(s)\n";
