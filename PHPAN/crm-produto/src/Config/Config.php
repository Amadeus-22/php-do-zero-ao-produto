<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

/**
 * Ponto ÚNICO de leitura de ambiente. Espalhar $_ENV['DB_HOST'] pelo código é o
 * mesmo erro de espalhar new PDO(...): acopla tudo à implementação.
 */
final class Config
{
    public static function carregar(): void
    {
        $raiz = dirname(__DIR__, 2);

        if (class_exists(\Dotenv\Dotenv::class)) {
            // safeLoad: em produção as variáveis podem vir do systemd/container,
            // sem arquivo .env em disco. O código funciona nos dois casos.
            \Dotenv\Dotenv::createImmutable($raiz)->safeLoad();
        }
    }

    /**
     * O `??` já elimina o null, e getenv() devolve string|false — por isso o
     * teste é só por false e string vazia (o PHPStan reclamou do `=== null`
     * redundante, e tinha razão).
     */
    private static function bruto(string $chave): string|false
    {
        $valor = $_ENV[$chave] ?? getenv($chave);

        // Variável de ambiente é sempre TEXTO na origem, mas $_ENV pode ter sido
        // populado por código com int/bool. Normalizar aqui evita TypeError em
        // quem lê — foi assim que a aula 1 do Módulo 7 quebrou na primeira vez.
        // getenv() devolve false quando a variável não existe. Tem que sair AQUI:
        // is_scalar(false) é true, e sem este early return "ausente" viraria '0'.
        if ($valor === false) {
            return false;
        }

        if (!is_scalar($valor)) {
            return false;
        }

        // $_ENV pode ter sido populado por código com int/bool — normaliza para
        // texto, que é o que uma variável de ambiente sempre é na origem.
        $texto = is_bool($valor) ? '1' : (string) $valor;

        return $texto === '' ? false : $texto;
    }

    public static function string(string $chave, ?string $padrao = null): string
    {
        $valor = self::bruto($chave);

        if ($valor === false) {
            // falha no BOOT com mensagem clara — não na primeira query 40min depois
            return $padrao ?? throw new RuntimeException("Config obrigatória ausente: {$chave}");
        }

        return (string) $valor;
    }

    /**
     * getenv() sempre devolve string. "false" é string não-vazia, logo TRUTHY em PHP —
     * sem FILTER_VALIDATE_BOOLEAN vem o clássico "desliguei o debug e continua ligado".
     */
    public static function bool(string $chave, bool $padrao = false): bool
    {
        $valor = self::bruto($chave);

        if ($valor === false) {
            return $padrao;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    public static function int(string $chave, ?int $padrao = null): int
    {
        $valor = self::bruto($chave);

        if ($valor === false) {
            return $padrao ?? throw new RuntimeException("Config obrigatória ausente: {$chave}");
        }

        return (int) $valor;
    }
}
