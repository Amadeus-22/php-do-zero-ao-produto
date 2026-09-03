<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class View
{
    /** @param array<string,mixed> $dados */
    public static function render(string $view, array $dados = [], ?string $layout = 'layouts/app'): string
    {
        $conteudo = self::renderizarArquivo($view, $dados);

        if ($layout === null) {
            return $conteudo;
        }

        return self::renderizarArquivo($layout, [...$dados, 'content' => $conteudo]);
    }

    /** Escape é a REGRA, não a exceção. */
    public static function e(mixed $valor): string
    {
        return htmlspecialchars(is_scalar($valor) ? (string) $valor : '', ENT_QUOTES, 'UTF-8');
    }

    /** @param array<string,mixed> $dados */
    private static function renderizarArquivo(string $view, array $dados): string
    {
        $caminho = self::caminho($view);

        if (!is_file($caminho)) {
            throw new RuntimeException("View não encontrada: {$view}");
        }

        extract($dados, EXTR_SKIP);
        ob_start();
        require $caminho;

        return (string) ob_get_clean();
    }

    private static function caminho(string $view): string
    {
        // sobe de src/Support/ até a raiz — não quebra se o projeto mudar de lugar
        return dirname(__DIR__, 2) . '/views/' . str_replace('.', '/', $view) . '.php';
    }
}
