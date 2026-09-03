<?php

declare(strict_types=1);

namespace App\Support;

/** Validação de FORMATO: síncrona, sem I/O. Duplicidade é regra de negócio (Service). */
final class Validator
{
    /** @var array<string, list<string>> */
    private array $erros = [];

    /** @param array<string, mixed> $dados */
    public function __construct(private readonly array $dados)
    {
    }

    public function required(string $campo, string $mensagem): self
    {
        if (trim((string) ($this->dados[$campo] ?? '')) === '') {
            $this->erros[$campo][] = $mensagem;
        }

        return $this; // devolve self para encadear as regras de cima para baixo
    }

    public function email(string $campo, string $mensagem): self
    {
        $valor = (string) ($this->dados[$campo] ?? '');

        if ($valor !== '' && filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
            $this->erros[$campo][] = $mensagem;
        }

        return $this;
    }

    public function max(string $campo, int $tamanho, string $mensagem): self
    {
        if (mb_strlen((string) ($this->dados[$campo] ?? '')) > $tamanho) {
            $this->erros[$campo][] = $mensagem;
        }

        return $this;
    }

    public function falhou(): bool
    {
        return $this->erros !== [];
    }

    /** @return array<string, list<string>> */
    public function erros(): array
    {
        return $this->erros;
    }
}
