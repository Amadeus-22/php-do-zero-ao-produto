<?php

declare(strict_types=1);

namespace App\Domain\Contato;

enum CanalPreferido: string
{
    case EMAIL = 'email';
    case TELEFONE = 'telefone';
    case WHATSAPP = 'whatsapp';
}
