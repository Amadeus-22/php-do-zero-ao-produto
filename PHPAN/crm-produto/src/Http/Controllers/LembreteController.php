<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Sessao;
use App\Http\PaginaDeErro;
use App\Http\Request;
use App\Http\Response;
use App\Support\Container;
use App\Support\Flash;
use App\Support\View;
use DateTimeImmutable;
use DateTimeZone;

final class LembreteController
{
    /** Fuso de exibição do painel. Em produção viria do perfil do usuário. */
    private const FUSO = 'America/Sao_Paulo';

    public function index(Request $request): Response
    {
        $usuarioId = Sessao::usuarioId();

        if ($usuarioId === null) {
            return PaginaDeErro::acessoRestrito();
        }

        return Response::html(View::render('lembretes/index', [
            'titulo' => 'Meus lembretes',
            'lembretes' => Container::lembreteService()->pendentesDe($usuarioId, self::FUSO),
        ]));
    }

    public function criar(Request $request, string $id): Response
    {
        $usuarioId = Sessao::usuarioId();
        $clienteId = (int) $id;

        if ($usuarioId === null) {
            return PaginaDeErro::acessoRestrito();
        }

        if (Container::clienteService()->buscarPorId($clienteId) === null) {
            return PaginaDeErro::naoEncontrado('Cliente não encontrado.');
        }

        $mensagem = $request->texto('mensagem');
        $quando = $request->texto('vence_em');

        if ($mensagem === '' || $quando === '') {
            Flash::erro('Informe a mensagem e a data do lembrete.');

            return Response::redirect("/clientes/{$clienteId}");
        }

        // O usuário digita no fuso DELE; o service converte para UTC ao gravar.
        $venceEm = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $quando, new DateTimeZone(self::FUSO));

        if ($venceEm === false) {
            Flash::erro('Data inválida.');

            return Response::redirect("/clientes/{$clienteId}");
        }

        Container::lembreteService()->criar($usuarioId, $clienteId, $mensagem, $venceEm);
        Flash::sucesso('Lembrete criado.');

        return Response::redirect("/clientes/{$clienteId}");
    }

    public function concluir(Request $request, string $id): Response
    {
        $usuarioId = Sessao::usuarioId();

        if ($usuarioId === null) {
            return PaginaDeErro::acessoRestrito();
        }

        // O service filtra por usuario_id: ninguém conclui lembrete alheio
        // mesmo adivinhando o id.
        Container::lembreteService()->concluir((int) $id, $usuarioId);
        Flash::sucesso('Lembrete concluído.');

        return Response::redirect('/lembretes');
    }
}
