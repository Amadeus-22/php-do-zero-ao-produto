<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    /** @param array<string,string> $headers */
    private function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
    ) {
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, $html, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self($status, json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), ['Content-Type' => 'application/json']);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self($status, '', ['Location' => $to]);
    }

    public static function arquivo(string $conteudo, string $nome, string $tipo): self
    {
        // basename no nome: se ele vier de parâmetro da requisição, um "../"
        // no Content-Disposition é injeção de cabeçalho.
        return new self(200, $conteudo, [
            'Content-Type' => $tipo,
            'Content-Disposition' => 'attachment; filename="' . basename($nome) . '"',
        ]);
    }

    /** Devolve uma cópia com um cabeçalho a mais — Response é imutável. */
    public function comCabecalho(string $nome, string $valor): self
    {
        return new self($this->status, $this->body, [...$this->headers, $nome => $valor]);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }
}
