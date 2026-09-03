<?php

declare(strict_types=1);

namespace App\Domain\Contato;

final class Contato
{
    /** @throws ContatoInvalido */
    public function __construct(
        private readonly ?int $id,
        private readonly int $clienteId,
        private string $nome,
        private readonly string $email,
        private CanalPreferido $canalPreferido,
    ) {
        if (trim($nome) === '') {
            throw ContatoInvalido::nomeVazio();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ContatoInvalido::emailInvalido($email);
        }

        $this->nome = trim($nome);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function canalPreferido(): CanalPreferido
    {
        return $this->canalPreferido;
    }

    public function alterarCanalPreferido(CanalPreferido $canal): void
    {
        $this->canalPreferido = $canal;
    }
}
