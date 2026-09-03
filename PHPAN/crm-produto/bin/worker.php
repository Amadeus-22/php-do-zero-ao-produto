<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use App\Support\Container;

/**
 * Worker da fila. Em produção fica de pé com supervisor/systemd (Módulo 7).
 * Sem ele rodando, os jobs só se acumulam como 'pendente' e nada acontece.
 *
 * Uso: php bin/worker.php [--once]
 */

Config::carregar();

$worker = Container::worker();
$umaVezSo = in_array('--once', $argv, true);

do {
    $processou = $worker->processarProximo();

    if (!$processou && !$umaVezSo) {
        sleep(2); // fila vazia: não fica queimando CPU
    }
} while (!$umaVezSo);

echo $processou ? "job processado\n" : "nada pendente\n";
