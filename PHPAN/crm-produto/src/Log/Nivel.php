<?php

declare(strict_types=1);

namespace App\Log;

/** Níveis PSR-3, reduzidos ao que se usa no dia a dia. */
enum Nivel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
}
