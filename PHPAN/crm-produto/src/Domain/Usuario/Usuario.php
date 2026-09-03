<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

final class Usuario
{
    private function __construct(
        private readonly ?int $id,
        private readonly string $nome,
        private readonly string $email,
        private readonly string $senhaHash,
        private readonly Papel $papel,
    ) {
    }

    /** Cria um usuário novo, aplicando o hash na senha em claro. */
    public static function novo(string $nome, string $email, string $senhaEmClaro, Papel $papel): self
    {
        $nome = trim($nome);
        $email = strtolower(trim($email));

        if ($nome === '') {
            throw UsuarioInvalido::nomeVazio();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw UsuarioInvalido::emailInvalido($email);
        }

        if (mb_strlen($senhaEmClaro) < 8) {
            throw UsuarioInvalido::senhaCurta();
        }

        return new self(null, $nome, $email, password_hash($senhaEmClaro, PASSWORD_DEFAULT), $papel);
    }

    /** Carrega um usuário existente: o hash JÁ é hash, não passa por password_hash de novo. */
    public static function reconstituir(int $id, string $nome, string $email, string $senhaHash, Papel $papel): self
    {
        return new self($id, $nome, $email, $senhaHash, $papel);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function senhaHash(): string
    {
        return $this->senhaHash;
    }

    public function papel(): Papel
    {
        return $this->papel;
    }

    /**
     * password_verify compara em tempo constante e entende o algoritmo embutido
     * no próprio hash — por isso nunca se compara hash com ===.
     */
    public function senhaConfere(string $senhaEmClaro): bool
    {
        return password_verify($senhaEmClaro, $this->senhaHash);
    }
}
