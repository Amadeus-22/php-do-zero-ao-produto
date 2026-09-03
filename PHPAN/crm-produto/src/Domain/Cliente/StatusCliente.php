<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

enum StatusCliente: string
{
    case ATIVO = 'ativo';
    case INATIVO = 'inativo';
}
