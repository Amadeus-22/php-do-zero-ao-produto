<?php

declare(strict_types=1);

namespace App\Domain\Contato;

use App\Domain\ErroDeDominio;

final class ContatoInvalido extends ErroDeDominio
{
    public static function nomeVazio(): self
    {
        return new self('Nome do contato é obrigatório.');
    }

    public static function emailInvalido(string $email): self
    {
        return new self("E-mail de contato inválido: {$email}");
    }
}
