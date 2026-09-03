<?php

declare(strict_types=1);

namespace App\Domain\Atividade;

enum TipoAtividade: string
{
    case LIGACAO = 'ligacao';
    case EMAIL = 'email';
    case REUNIAO = 'reuniao';
    case NOTA = 'nota';
}
