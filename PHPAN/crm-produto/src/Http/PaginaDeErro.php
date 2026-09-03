<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\View;

/**
 * Fábrica das respostas de erro em HTML.
 *
 * Existe para que nenhuma classe PHP carregue marcação: antes, Router e
 * middlewares faziam Response::html('<h1>404 ...</h1>'). HTML é da camada de
 * apresentação e mora em views/erros/*.php — assim dá para estilizar a página
 * de erro sem abrir o roteador.
 */
final class PaginaDeErro
{
    public static function naoEncontrado(?string $mensagem = null): Response
    {
        return self::render('404', 404, $mensagem);
    }

    public static function acessoRestrito(?string $mensagem = null): Response
    {
        return self::render('403', 403, $mensagem);
    }

    public static function sessaoExpirada(?string $mensagem = null): Response
    {
        return self::render('419', 419, $mensagem);
    }

    private static function render(string $view, int $status, ?string $mensagem): Response
    {
        return Response::html(
            View::render("erros/{$view}", ['titulo' => "Erro {$status}", 'mensagem' => $mensagem]),
            $status,
        );
    }
}
