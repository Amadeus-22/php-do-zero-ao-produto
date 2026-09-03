<?php

declare(strict_types=1);

namespace App\Log;

/**
 * Log estruturado: uma linha = um objeto JSON (formato JSON Lines).
 *
 * Texto livre ("Erro ao salvar cliente") funciona até você precisar filtrar,
 * buscar ou agregar milhares de linhas. Com JSON por linha, cada campo é
 * pesquisável com grep, jq ou qualquer ferramenta de log.
 *
 * O JSON aqui é FORMATO DE SAÍDA, não armazenamento: nada relê este arquivo
 * como se fosse banco.
 */
final class Logger
{
    /** Campos que nunca são gravados, em qualquer nível de profundidade. */
    private const SENSIVEIS = ['senha', 'senha_hash', 'password', 'token', 'token_hash', 'access', 'refresh', 'authorization', 'cartao', 'cvv'];

    public function __construct(
        private readonly string $arquivo,
    ) {
        $pasta = dirname($this->arquivo);

        if (!is_dir($pasta)) {
            mkdir($pasta, 0o775, true);
        }
    }

    /** @param array<string, mixed> $contexto */
    public function log(Nivel $nivel, string $mensagem, array $contexto = []): void
    {
        $linha = json_encode([
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'nivel' => $nivel->value,
            'mensagem' => $mensagem,
            'contexto' => self::limpar($contexto),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        file_put_contents($this->arquivo, $linha . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** @param array<string, mixed> $contexto */
    public function debug(string $m, array $contexto = []): void
    {
        $this->log(Nivel::DEBUG, $m, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public function info(string $m, array $contexto = []): void
    {
        $this->log(Nivel::INFO, $m, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public function warning(string $m, array $contexto = []): void
    {
        $this->log(Nivel::WARNING, $m, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public function error(string $m, array $contexto = []): void
    {
        $this->log(Nivel::ERROR, $m, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public function critical(string $m, array $contexto = []): void
    {
        $this->log(Nivel::CRITICAL, $m, $contexto);
    }

    /**
     * @param array<string, mixed> $contexto
     * @return array<string, mixed>
     */
    private static function limpar(array $contexto): array
    {
        foreach ($contexto as $chave => $valor) {
            if (in_array(strtolower((string) $chave), self::SENSIVEIS, true)) {
                $contexto[$chave] = '[REMOVIDO]';
                continue;
            }

            if (is_array($valor)) {
                $contexto[$chave] = self::limpar($valor);
            }
        }

        return $contexto;
    }
}
