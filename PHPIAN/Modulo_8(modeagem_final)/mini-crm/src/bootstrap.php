<?php
declare(strict_types=1);

/**
 * Ponto único de partida. Toda página em public/ começa com:
 *   require __DIR__ . '/../src/bootstrap.php';
 */

define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', APP_ROOT . '/public');

require __DIR__ . '/helpers.php';

// Erros na tela só em desenvolvimento.
ini_set('display_errors', config('debug') ? '1' : '0');
error_reporting(config('debug') ? E_ALL : 0);

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/contatos.php';

// Scripts de CLI (seed) não precisam de sessão.
if (PHP_SAPI !== 'cli') {
    sessao_iniciar();
}
