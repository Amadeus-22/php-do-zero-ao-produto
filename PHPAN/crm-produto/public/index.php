<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use App\Http\Kernel;
use App\Http\Request;

Config::carregar();

// APP_ENV decide o que o usuário vê quando algo quebra (Módulo 7, aula 3)
if (Config::string('APP_ENV', 'production') !== 'production') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

// Cookie de sessão endurecido (Módulo 5, aula 1)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');

if (($_SERVER['HTTPS'] ?? '') !== '') {
    ini_set('session.cookie_secure', '1');
}

session_start();

Kernel::router()->dispatch(Request::fromGlobals());
