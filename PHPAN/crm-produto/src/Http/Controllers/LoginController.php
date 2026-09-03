<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Sessao;
use App\Http\Request;
use App\Http\Response;
use App\Support\Container;
use App\Support\Flash;
use App\Support\View;

final class LoginController
{
    public function formulario(Request $request): Response
    {
        return Response::html(View::render('login', ['titulo' => 'Entrar', 'antigo' => []]));
    }

    public function entrar(Request $request): Response
    {
        $email = $request->texto('email');
        $ip = (string) ($request->server['REMOTE_ADDR'] ?? 'desconhecido');

        $resultado = Container::loginPainel()->autenticar($email, $request->texto('senha'), $ip);

        if ($resultado->bloqueado) {
            return Response::html(View::render('login', [
                'titulo' => 'Entrar',
                'erro' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                'antigo' => ['email' => $email],
            ]), 429)->comCabecalho('Retry-After', (string) $resultado->tenteEmSegundos);
        }

        if (!$resultado->autenticado) {
            // Mesma mensagem para e-mail inexistente e senha errada: dizer qual dos
            // dois falhou entrega ao atacante quais e-mails têm conta.
            return Response::html(View::render('login', [
                'titulo' => 'Entrar',
                'erro' => 'E-mail ou senha inválidos.',
                'antigo' => ['email' => $email],
            ]), 401);
        }

        Flash::sucesso('Bem-vindo, ' . ($resultado->usuario?->nome() ?? ''));

        return Response::redirect('/clientes');
    }

    public function sair(Request $request): Response
    {
        Sessao::sair();

        return Response::redirect('/login');
    }
}
