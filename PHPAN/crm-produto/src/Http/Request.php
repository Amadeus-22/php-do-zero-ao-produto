<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $body
     * @param array<string,mixed> $server
     */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $server,
        public readonly string $corpoCru = '',
    ) {
    }

    /** Corpo exatamente como chegou — necessário para verificar assinatura HMAC. */
    public function corpoCru(): string
    {
        return $this->corpoCru;
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper(is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'GET');
        $uri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        $contentType = is_string($_SERVER['CONTENT_TYPE'] ?? null) ? $_SERVER['CONTENT_TYPE'] : '';
        $body = $_POST;
        $bruto = (string) file_get_contents('php://input');

        if (str_starts_with($contentType, 'application/json')) {
            $decodificado = json_decode($bruto === '' ? '{}' : $bruto, true);
            $body = is_array($decodificado) ? $decodificado : [];
        }

        // rtrim resolve /clientes e /clientes/ como a MESMA rota — decidido uma vez, aqui.
        return new self($method, rtrim($path, '/') ?: '/', $_GET, $body, $_SERVER, $bruto);
    }

    /** Fábrica para teste: monta uma requisição sem depender das superglobais. */
    public static function falsa(
        string $method,
        string $path,
        array $body = [],
        array $query = [],
        array $server = [],
        string $corpoCru = '',
    ): self {
        return new self(strtoupper($method), rtrim($path, '/') ?: '/', $query, $body, $server, $corpoCru);
    }

    /** Açúcar para teste: monta a requisição já autenticada por Bearer. */
    public static function comToken(string $method, string $path, string $token, array $body = [], array $query = []): self
    {
        return self::falsa($method, $path, $body, $query, ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function texto(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        $texto = is_scalar($v) ? trim((string) $v) : $default;

        return $texto === '' ? $default : $texto;
    }
}
