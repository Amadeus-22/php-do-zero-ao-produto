<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

use App\Domain\ErroDeDominio;

final class ClienteNaoEncontrado extends ErroDeDominio
{
    public static function comId(int $id): self
    {
        return new self("Cliente com ID {$id} não foi encontrado.");
    }

    public static function comEmail(string $email): self
    {
        return new self("Cliente com e-mail {$email} não foi encontrado.");
    }
}
