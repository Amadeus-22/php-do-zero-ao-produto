<?php

declare(strict_types=1);

namespace App\Validation;

use App\Support\Validator;

/** Fonte ÚNICA das regras de formato do cliente: painel web e API usam esta classe. */
final class ClienteValidator
{
    /** @param array<string, mixed> $dados */
    public static function validar(array $dados): Validator
    {
        return (new Validator($dados))
            ->required('nome', 'Informe o nome do cliente.')
            ->max('nome', 120, 'Nome muito longo (máx. 120 caracteres).')
            ->required('email', 'Informe o e-mail do cliente.')
            ->email('email', 'E-mail inválido.')
            ->max('telefone', 20, 'Telefone muito longo (máx. 20 caracteres).');
    }
}
