<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

final class Cliente
{
    private function __construct(
        private readonly ?int $id,
        private string $nome,
        private readonly string $email,
        private ?string $telefone,
        private StatusCliente $status,
        private readonly \DateTimeImmutable $criadoEm,
    ) {
    }

    /**
     * Criação pela primeira vez: valida tudo e define criadoEm como agora.
     *
     * @throws ClienteInvalido
     */
    public static function novo(string $nome, string $email, ?string $telefone = null): self
    {
        $nome = trim($nome);

        if ($nome === '') {
            throw ClienteInvalido::nomeVazio();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ClienteInvalido::emailInvalido($email);
        }

        return new self(null, $nome, $email, self::normalizarTelefone($telefone), StatusCliente::ATIVO, new \DateTimeImmutable());
    }

    /**
     * Carregamento de um cliente que JÁ existe: não revalida regras de criação,
     * porque o dado já passou por elas quando foi salvo.
     */
    public static function reconstituir(
        int $id,
        string $nome,
        string $email,
        StatusCliente $status,
        \DateTimeImmutable $criadoEm,
        ?string $telefone = null,
    ): self {
        return new self($id, $nome, $email, self::normalizarTelefone($telefone), $status, $criadoEm);
    }

    private static function normalizarTelefone(?string $telefone): ?string
    {
        $telefone = trim((string) $telefone);

        return $telefone === '' ? null : $telefone;
    }

    public function telefone(): ?string
    {
        return $this->telefone;
    }

    public function alterarTelefone(?string $telefone): void
    {
        $this->telefone = self::normalizarTelefone($telefone);
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

    public function status(): StatusCliente
    {
        return $this->status;
    }

    public function criadoEm(): \DateTimeImmutable
    {
        return $this->criadoEm;
    }

    public function estaAtivo(): bool
    {
        return $this->status === StatusCliente::ATIVO;
    }

    /** Nome de intenção de negócio, não setNome(). Revalida a cada mudança. */
    public function renomear(string $novoNome): void
    {
        $novoNome = trim($novoNome);

        if ($novoNome === '') {
            throw ClienteInvalido::nomeVazio();
        }

        $this->nome = $novoNome;
    }

    public function desativar(): void
    {
        $this->status = StatusCliente::INATIVO;
    }
}
