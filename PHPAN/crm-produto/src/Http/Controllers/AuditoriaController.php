<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Sessao;
use App\Domain\Usuario\Gate;
use App\Http\PaginaDeErro;
use App\Http\Request;
use App\Http\Response;
use App\Support\Container;
use App\Support\View;

final class AuditoriaController
{
    public function porEntidade(Request $request, string $entidade, string $id): Response
    {
        $papel = Sessao::papel();

        // Rastro de quem fez o quê é informação sensível sobre PESSOAS, não só
        // sobre dados. Só admin vê.
        if ($papel === null || !(new Gate())->pode($papel, 'auditoria.ver')) {
            return PaginaDeErro::acessoRestrito('Somente administradores veem a auditoria.');
        }

        $historico = Container::auditoria()->historicoDe($entidade, (int) $id);
        $cliente = $entidade === 'cliente' ? Container::clienteService()->buscarPorId((int) $id) : null;

        return Response::html(View::render('auditoria/historico', [
            'titulo' => 'Auditoria',
            'entidade' => $entidade,
            'entidadeId' => (int) $id,
            'nome' => $cliente?->nome(),
            'historico' => $historico,
        ]));
    }
}
