<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Carrega SQL de arquivo .sql. Existe para que DDL não more dentro de heredoc
 * em arquivo PHP: schema é SQL, e SQL fica em arquivo .sql — revisável, com
 * destaque de sintaxe no editor e diff legível no Git.
 */
final class Sql
{
    public static function de(string $caminho): string
    {
        $conteudo = @file_get_contents($caminho);

        if ($conteudo === false) {
            throw new RuntimeException("Arquivo SQL não encontrado: {$caminho}");
        }

        return trim($conteudo);
    }

    /** @return list<string> Cada comando do arquivo, sem os vazios. */
    public static function comandos(string $caminho): array
    {
        $comandos = array_map('trim', explode(';', self::de($caminho)));

        return array_values(array_filter($comandos, static fn (string $c): bool => $c !== ''));
    }
}
