<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Cliente\ClienteService;
use App\Billing\LimiteDoPlanoAtingido;
use App\Domain\Cliente\ClienteNaoEncontrado;
use App\Domain\Cliente\EmailJaCadastrado;
use App\Http\PaginaDeErro;
use App\Http\Request;
use App\Http\Response;
use App\Support\Container;
use App\Support\Flash;
use App\Support\View;
use App\Validation\ClienteValidator;

/** Controller FINO: lê o Request, chama UM método do Service, escolhe o formato. */
final class ClienteController
{
    private ClienteService $service;

    public function __construct()
    {
        $this->service = Container::clienteService();
    }

    public function index(Request $request): Response
    {
        return Response::html(View::render('clientes/index', [
            'titulo' => 'Clientes',
            'clientes' => $this->service->listar(),
        ]));
    }

    public function novo(Request $request): Response
    {
        return Response::html(View::render('clientes/novo', [
            'titulo' => 'Novo cliente',
            'erros' => [],
            'antigo' => [],
        ]));
    }

    public function show(Request $request, string $id): Response
    {
        try {
            $cliente = $this->service->buscar((int) $id);
        } catch (ClienteNaoEncontrado $e) {
            return PaginaDeErro::naoEncontrado($e->getMessage());
        }

        return Response::html(View::render('clientes/show', [
            'titulo' => $cliente->nome(),
            'cliente' => $cliente,
            'anexos' => Container::repositorioDeAnexos()->doCliente((int) $id),
        ]));
    }

    public function criar(Request $request): Response
    {
        $dados = [
            'nome' => $request->texto('nome'),
            'email' => $request->texto('email'),
            'telefone' => $request->texto('telefone'),
        ];

        // 1) formato (rápido, sem I/O)
        $validator = ClienteValidator::validar($dados);

        if ($validator->falhou()) {
            return Response::html(View::render('clientes/novo', [
                'titulo' => 'Novo cliente',
                'erros' => $validator->erros(),
                'antigo' => $dados, // old input: o usuário não redigita tudo
            ]), 422);
        }

        // 2) regra de negócio (precisa consultar)
        try {
            $this->service->criar($dados);
        } catch (LimiteDoPlanoAtingido $e) {
            // Mesma regra do Service, outra tradução: aqui vira mensagem na tela.
            Flash::erro($e->getMessage());

            return Response::redirect('/clientes');
        } catch (EmailJaCadastrado $e) {
            return Response::html(View::render('clientes/novo', [
                'titulo' => 'Novo cliente',
                'erros' => ['email' => [$e->getMessage()]],
                'antigo' => $dados,
            ]), 422);
        }

        Flash::sucesso('Cliente criado com sucesso.');

        return Response::redirect('/clientes');
    }

    public function remover(Request $request, string $id): Response
    {
        try {
            $this->service->remover((int) $id);
            Flash::sucesso('Cliente removido.');
        } catch (ClienteNaoEncontrado $e) {
            Flash::erro($e->getMessage());
        }

        return Response::redirect('/clientes');
    }
}
