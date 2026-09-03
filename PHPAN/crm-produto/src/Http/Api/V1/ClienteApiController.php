<?php

declare(strict_types=1);

namespace App\Http\Api\V1;

use App\Application\Cliente\ClienteService;
use App\Billing\LimiteDoPlanoAtingido;
use App\Domain\Cliente\ClienteNaoEncontrado;
use App\Domain\Cliente\CriterioDeBusca;
use App\Domain\Cliente\EmailJaCadastrado;
use App\Http\ApiError;
use App\Http\ApiResponse;
use App\Http\Middleware\ExigirTokenApi;
use App\Http\Request;
use App\Http\Resources\ClienteResource;
use App\Http\Response;
use App\Support\Container;
use App\Validation\ClienteValidator;

/**
 * A CASCA da API é que tem versão. O ClienteService NÃO tem sufixo V1 —
 * ele é do produto, não da versão da API.
 */
final class ClienteApiController
{
    private ClienteService $service;

    public function __construct()
    {
        $this->service = Container::clienteService();
    }

    public function index(Request $request): Response
    {
        $resultado = $this->service->listarPaginado(self::criterio($request->query));

        return ApiResponse::lista(ClienteResource::colecao($resultado['itens']), $resultado['meta']);
    }

    public function show(Request $request, string $id): Response
    {
        $cliente = $this->service->buscarPorId((int) $id);

        if ($cliente === null) {
            return ApiError::make('not_found', 'Cliente não encontrado.', 404);
        }

        return ApiResponse::ok(ClienteResource::um($cliente));
    }

    public function store(Request $request): Response
    {
        // Montagem campo a campo: se vier "id" ou "senha_hash" no JSON, é
        // simplesmente ignorado. É assim que se evita mass assignment.
        $dados = self::dados($request);

        $validator = ClienteValidator::validar($dados);

        if ($validator->falhou()) {
            return ApiError::make('validation_failed', 'Dados inválidos.', 422, $validator->erros());
        }

        try {
            $cliente = $this->service->criar($dados, ExigirTokenApi::$usuarioId);

            // A resposta sai AGORA; o e-mail é problema do worker.
            Container::dispatcher()->despachar('enviar_email_boas_vindas', ['cliente_id' => $cliente->id()]);
        } catch (LimiteDoPlanoAtingido $e) {
            // 403: autenticado e com permissão de papel, mas o PLANO não permite.
            return ApiError::make('plan_limit_reached', $e->getMessage(), 403);
        } catch (EmailJaCadastrado $e) {
            return ApiError::make('conflict', $e->getMessage(), 409);
        }

        return ApiResponse::ok(ClienteResource::um($cliente), 201);
    }

    public function update(Request $request, string $id): Response
    {
        $dados = self::dados($request);
        $validator = ClienteValidator::validar($dados);

        if ($validator->falhou()) {
            return ApiError::make('validation_failed', 'Dados inválidos.', 422, $validator->erros());
        }

        try {
            $cliente = $this->service->atualizar((int) $id, $dados, ExigirTokenApi::$usuarioId);
        } catch (ClienteNaoEncontrado $e) {
            return ApiError::make('not_found', $e->getMessage(), 404);
        } catch (LimiteDoPlanoAtingido $e) {
            return ApiError::make('plan_limit_reached', $e->getMessage(), 403);
        } catch (EmailJaCadastrado $e) {
            return ApiError::make('conflict', $e->getMessage(), 409);
        }

        return ApiResponse::ok(ClienteResource::um($cliente));
    }

    public function destroy(Request $request, string $id): Response
    {
        try {
            $this->service->remover((int) $id, ExigirTokenApi::$usuarioId);
        } catch (ClienteNaoEncontrado $e) {
            return ApiError::make('not_found', $e->getMessage(), 404);
        }

        return Response::json(null, 204);
    }

    public function lixeira(Request $request): Response
    {
        return ApiResponse::ok(ClienteResource::colecao($this->service->lixeira()));
    }

    public function restaurar(Request $request, string $id): Response
    {
        $this->service->restaurar((int) $id, ExigirTokenApi::$usuarioId);

        $cliente = $this->service->buscarPorId((int) $id);

        return $cliente === null
            ? ApiError::make('not_found', 'Cliente não encontrado na lixeira.', 404)
            : ApiResponse::ok(ClienteResource::um($cliente));
    }

    /** Exportação grande vai para a fila em vez de segurar o request. */
    public function exportar(Request $request): Response
    {
        $formato = $request->texto('formato', 'csv');
        $exportador = Container::exportadorCsv();

        if ($exportador->deveIrParaFila()) {
            Container::dispatcher()->despachar('gerar_relatorio_clientes', [
                'referencia' => date('Y-m-d'),
                'usuario_id' => ExigirTokenApi::$usuarioId,
            ]);

            return ApiResponse::ok([
                'mensagem' => 'Exportação grande — você será avisado quando estiver pronta.',
                'total' => $exportador->total(),
            ], 202);
        }

        if ($formato === 'pdf') {
            // Mesma interface do CSV: quem chama não sabe qual gerador está por trás.
            $linhas = array_map(
                static fn ($c): array => [
                    'id' => (int) $c->id(),
                    'nome' => $c->nome(),
                    'email' => $c->email(),
                    'telefone' => $c->telefone() ?? '-',
                ],
                $this->service->listar(),
            );

            return Response::arquivo(
                Container::geradorDePdf()->gerar('Clientes', $linhas),
                'clientes.pdf',
                'application/pdf',
            );
        }

        $buffer = fopen('php://temp', 'r+');

        if ($buffer === false) {
            return ApiError::make('server_error', 'Falha ao gerar o arquivo.', 500);
        }

        $exportador->escrever($buffer);
        rewind($buffer);
        $csv = (string) stream_get_contents($buffer);
        fclose($buffer);

        return Response::arquivo($csv, 'clientes.csv', 'text/csv; charset=UTF-8');
    }

    /** @return array{nome:string, email:string, telefone:?string} */
    private static function dados(Request $request): array
    {
        $telefone = $request->texto('telefone');

        return [
            'nome' => $request->texto('nome'),
            'email' => $request->texto('email'),
            'telefone' => $telefone === '' ? null : $telefone,
        ];
    }

    /** @param array<string, mixed> $query */
    private static function criterio(array $query): CriterioDeBusca
    {
        $q = isset($query['q']) ? trim((string) $query['q']) : null;

        return new CriterioDeBusca(
            page: max(1, (int) ($query['page'] ?? 1)),
            // teto de 100: sem isso, per_page=999999 derruba o servidor
            perPage: min(100, max(1, (int) ($query['per_page'] ?? 20))),
            q: $q === '' ? null : $q,
            ativo: array_key_exists('ativo', $query)
                ? filter_var($query['ativo'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,
        );
    }
}
