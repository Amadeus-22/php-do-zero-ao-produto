<?php

declare(strict_types=1);

namespace App\Domain\Notificacao;

interface RemetenteDeEmail
{
    public function enviar(string $destinatario, string $assunto, string $corpo): void;
}
