<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use App\Support\Container;

// Cron, a cada 5 minutos:
//     */5 * * * * php /caminho/bin/verificar-lembretes.php
//
// (Escrito em comentário de LINHA de propósito: dentro de um bloco /* */ o "*/"
//  do cron fecharia o comentário e o arquivo deixaria de compilar. Aconteceu.)
//
// Só DESPACHA o job; quem envia o e-mail é o worker — assim o cron termina rápido
// e o envio ganha retry e backoff de graça.

Config::carregar();

echo Container::lembreteService()->despacharVencidos(), " lembrete(s) despachado(s)\n";
