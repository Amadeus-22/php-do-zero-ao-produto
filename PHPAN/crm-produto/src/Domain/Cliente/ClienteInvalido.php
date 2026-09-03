<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

use App\Domain\ErroDeDominio;

final class ClienteInvalido extends ErroDeDominio
{
    public static function nomeVazio(): self
    {
        return new self('Nome do cliente é obrigatório.');
    }

    public static function emailInvalido(string $email): self
    {
        return new self("E-mail inválido: {$email}");
    }
}
