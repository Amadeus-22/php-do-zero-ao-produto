<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Sessao;
use App\Domain\Usuario\Gate;
use App\Http\PaginaDeErro;
use App\Http\Request;
use App\Http\Response;
use App\Support\Container;
use App\Support\Flash;
use App\Uploads\UploadInvalido;

final class AnexoController
{
    public function enviar(Request $request, string $id): Response
    {
        $clienteId = (int) $id;
        $papel = Sessao::papel();

        if ($papel === null || !(new Gate())->pode($papel, 'cliente.editar')) {
            return PaginaDeErro::acessoRestrito('Seu papel não permite anexar arquivos.');
        }

        if (Container::clienteService()->buscarPorId($clienteId) === null) {
            return PaginaDeErro::naoEncontrado('Cliente não encontrado.');
        }

        /** @var array{tmp_name: string, error: int, size: int, name: string}|null $arquivo */
        $arquivo = $_FILES['arquivo'] ?? null;

        if ($arquivo === null) {
            Flash::erro('Nenhum arquivo enviado.');

            return Response::redirect("/clientes/{$clienteId}");
        }

        try {
            $resultado = Container::uploadService()->armazenar($arquivo);
        } catch (UploadInvalido $e) {
            Flash::erro($e->getMessage());

            return Response::redirect("/clientes/{$clienteId}");
        }

        Container::repositorioDeAnexos()->registrar(
            $clienteId,
            $resultado->nomeOriginal,
            $resultado->nomeArmazenado,
            $resultado->mimeReal,
            $resultado->tamanhoBytes,
            Sessao::usuarioId(),
        );

        Container::auditoria()->registrar(
            Sessao::usuarioId(),
            'anexo.enviado',
            'cliente',
            $clienteId,
            dadosDepois: ['arquivo' => $resultado->nomeOriginal, 'mime' => $resultado->mimeReal],
        );

        Flash::sucesso('Anexo enviado.');

        return Response::redirect("/clientes/{$clienteId}");
    }

    /**
     * Download SEMPRE por rota, nunca link estático.
     *
     * Anexo de cliente é dado sensível: servido direto de public/, qualquer um
     * com a URL baixa — e a URL vaza em log, histórico e Referer.
     */
    public function baixar(Request $request, string $id): Response
    {
        $papel = Sessao::papel();

        if ($papel === null || !(new Gate())->pode($papel, 'cliente.ver')) {
            return PaginaDeErro::acessoRestrito();
        }

        $anexo = Container::repositorioDeAnexos()->buscarPorId((int) $id);

        if ($anexo === null) {
            return PaginaDeErro::naoEncontrado('Anexo não encontrado.');
        }

        // basename no nome ARMAZENADO: ele veio do banco, mas o caminho é montado
        // por concatenação — um "../" ali seria leitura de arquivo arbitrário.
        $caminho = dirname(__DIR__, 3) . '/storage/anexos/' . basename((string) $anexo['nome_armazenado']);

        if (!is_file($caminho)) {
            return PaginaDeErro::naoEncontrado('Arquivo não está mais disponível.');
        }

        return Response::arquivo(
            (string) file_get_contents($caminho),
            (string) $anexo['nome_original'],
            (string) $anexo['mime_real'],
        );
    }
}
