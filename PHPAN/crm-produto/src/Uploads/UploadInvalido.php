<?php

declare(strict_types=1);

namespace App\Uploads;

use App\Domain\ErroDeDominio;

final class UploadInvalido extends ErroDeDominio
{
    public static function falhaNoEnvio(int $codigo): self
    {
        return new self("Falha no envio do arquivo (erro {$codigo}).");
    }

    public static function grandeDemais(int $limiteBytes): self
    {
        return new self('Arquivo maior que o limite permitido (' . (int) ($limiteBytes / 1048576) . ' MB).');
    }

    public static function tipoNaoPermitido(string $mimeReal): self
    {
        return new self("Tipo de arquivo não permitido: {$mimeReal}");
    }

    public static function envioSuspeito(): self
    {
        return new self('Envio inválido.');
    }
}
