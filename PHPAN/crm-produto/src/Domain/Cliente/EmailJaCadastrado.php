<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

use App\Domain\ErroDeDominio;

final class EmailJaCadastrado extends ErroDeDominio
{
    public function __construct(
        private readonly string $email,
    ) {
        // Sem este parent::__construct(), getMessage() voltaria vazio.
        parent::__construct("O e-mail {$this->email} já está cadastrado.");
    }

    public function email(): string
    {
        return $this->email;
    }
}
