<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Support\Container;
use App\Support\Flash;
use App\Support\View;

final class ResetSenhaController
{
    /**
     * MESMA mensagem exista ou não a conta. Qualquer diferença — texto, status
     * ou até tempo de resposta — permite enumerar quais e-mails têm cadastro.
     */
    private const MENSAGEM_UNICA = 'Se esse e-mail estiver cadastrado, você vai receber um link em instantes.';

    public function formularioSolicitar(Request $request): Response
    {
        return Response::html(View::render('senha/esqueci', ['titulo' => 'Esqueci minha senha']));
    }

    public function solicitar(Request $request): Response
    {
        $email = $request->texto('email');
        $ip = (string) ($request->server['REMOTE_ADDR'] ?? 'desconhecido');

        // Rate limit aqui também: sem ele, o endpoint vira spammer de e-mail e
        // canal de enumeração por força bruta.
        if (Container::rateLimiter()->atingiu("reset:{$email}:{$ip}", 3, 900)) {
            return Response::html(View::render('senha/esqueci', [
                'titulo' => 'Esqueci minha senha',
                'mensagem' => self::MENSAGEM_UNICA,
            ]), 429)->comCabecalho('Retry-After', '900');
        }

        Container::resetSenhaService()->solicitar($email);

        return Response::html(View::render('senha/esqueci', [
            'titulo' => 'Esqueci minha senha',
            'mensagem' => self::MENSAGEM_UNICA,
        ]));
    }

    public function formularioRedefinir(Request $request): Response
    {
        return Response::html(View::render('senha/redefinir', [
            'titulo' => 'Redefinir senha',
            'token' => $request->texto('token'),
        ]));
    }

    public function redefinir(Request $request): Response
    {
        $token = $request->texto('token');

        if (!Container::resetSenhaService()->redefinir($token, $request->texto('senha'))) {
            // Mensagem única de novo: não dizemos se o token é inválido, expirado
            // ou se a senha era curta demais para um token que nem existe.
            return Response::html(View::render('senha/redefinir', [
                'titulo' => 'Redefinir senha',
                'token' => $token,
                'erro' => 'Link inválido ou expirado, ou senha com menos de 8 caracteres.',
            ]), 400);
        }

        Flash::sucesso('Senha redefinida. Faça login com a nova senha.');

        return Response::redirect('/login');
    }
}
