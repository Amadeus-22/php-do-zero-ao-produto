<?php

declare(strict_types=1);

namespace App\Infrastructure\Notificacao;

use App\Domain\Notificacao\RemetenteDeEmail;

/** Implementação de desenvolvimento: grava em arquivo em vez de enviar. */
final readonly class RemetenteDeEmailEmLog implements RemetenteDeEmail
{
    public function __construct(
        private string $caminhoDoArquivoDeLog,
    ) {
    }

    public function enviar(string $destinatario, string $assunto, string $corpo): void
    {
        $linha = sprintf(
            "[%s] Para: %s | Assunto: %s\n%s\n---\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $destinatario,
            $assunto,
            $corpo,
        );

        file_put_contents($this->caminhoDoArquivoDeLog, $linha, FILE_APPEND);
    }
}
