<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

use App\Domain\ErroDeDominio;

final class UsuarioInvalido extends ErroDeDominio
{
    public static function nomeVazio(): self
    {
        return new self('Nome do usuário é obrigatório.');
    }

    public static function emailInvalido(string $email): self
    {
        return new self("E-mail de usuário inválido: {$email}");
    }

    public static function senhaCurta(): self
    {
        return new self('Senha precisa ter pelo menos 8 caracteres.');
    }
}
