<?php

declare(strict_types=1);

namespace App\Application\Cliente;

use App\Auditoria\Auditoria;
use App\Auditoria\AuditoriaNula;
use App\Billing\LimiteDoPlanoAtingido;
use App\Billing\PlanLimiter;
use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\ClienteNaoEncontrado;
use App\Domain\Cliente\CriterioDeBusca;
use App\Domain\Cliente\EmailJaCadastrado;
use App\Domain\Cliente\RepositorioDeClientes;

/**
 * Regra de negócio do CRM. NÃO conhece $_POST, Response nem HTML —
 * é isso que permite painel web (M3) e API (M4) usarem o MESMO código.
 */
final readonly class ClienteService
{
    public function __construct(
        private RepositorioDeClientes $clientes,
        // Chamada explícita de propósito: a aula evita "mágica com eventos
        // escondidos" nesta fase — é mais fácil garantir que ninguém esqueceu
        // quando a chamada está à vista. AuditoriaNula cobre o teste de unidade.
        private Auditoria $auditoria = new AuditoriaNula(),
        // null = sem controle de plano (testes de unidade e scripts internos).
        // Checar aqui, e não no controller, é o que garante que painel e API
        // passem pela MESMA regra — o erro que a aula 1 do Módulo 8 alerta.
        private ?PlanLimiter $limites = null,
    ) {
    }

    /** @return list<Cliente> */
    public function listar(): array
    {
        return $this->clientes->todos();
    }

    /** @return array{itens: list<Cliente>, meta: array{page:int, per_page:int, total:int, total_pages:int}} */
    public function listarPaginado(CriterioDeBusca $criterio): array
    {
        $total = $this->clientes->contar($criterio);

        return [
            'itens' => $this->clientes->buscar($criterio),
            'meta' => [
                'page' => $criterio->page,
                'per_page' => $criterio->perPage,
                'total' => $total,
                'total_pages' => max(1, (int) ceil($total / $criterio->perPage)),
            ],
        ];
    }

    /** @throws ClienteNaoEncontrado */
    public function buscar(int $id): Cliente
    {
        return $this->clientes->buscarPorId($id) ?? throw ClienteNaoEncontrado::comId($id);
    }

    public function buscarPorId(int $id): ?Cliente
    {
        return $this->clientes->buscarPorId($id);
    }

    /**
     * @param array{nome:string, email:string, telefone?:string|null} $dados
     * @throws EmailJaCadastrado|\App\Domain\Cliente\ClienteInvalido
     */
    /**
     * @param array{nome:string, email:string, telefone?:string|null} $dados
     * @throws LimiteDoPlanoAtingido|EmailJaCadastrado|\App\Domain\Cliente\ClienteInvalido
     */
    public function criar(array $dados, ?int $usuarioLogadoId = null, int $contaId = 1): Cliente
    {
        $this->exigirPermissaoDePlano($contaId);

        $email = strtolower(trim($dados['email']));

        // Regra de NEGÓCIO (precisa de I/O) — não cabe no Validator de formato.
        if ($this->clientes->buscarPorEmail($email) !== null) {
            throw new EmailJaCadastrado($email);
        }

        $cliente = $this->clientes->salvar(
            Cliente::novo($dados['nome'], $email, $dados['telefone'] ?? null),
        );

        $this->auditar($usuarioLogadoId, 'cliente.criado', $cliente, depois: self::instantaneo($cliente));

        return $cliente;
    }

    /**
     * @param array{nome:string, email:string, telefone?:string|null} $dados
     * @throws ClienteNaoEncontrado|EmailJaCadastrado|LimiteDoPlanoAtingido
     */
    public function atualizar(int $id, array $dados, ?int $usuarioLogadoId = null, int $contaId = 1): Cliente
    {
        // Editar não consome cota nova, mas assinatura vencida bloqueia escrita.
        $this->exigirAssinaturaAtiva($contaId);

        $atual = $this->buscar($id);
        $email = strtolower(trim($dados['email']));

        $existente = $this->clientes->buscarPorEmail($email);

        if ($existente !== null && $existente->id() !== $id) {
            throw new EmailJaCadastrado($email);
        }

        $atualizado = $this->clientes->salvar(Cliente::reconstituir(
            id: $id,
            nome: trim($dados['nome']),
            email: $email,
            status: $atual->status(),
            criadoEm: $atual->criadoEm(),
            telefone: $dados['telefone'] ?? null,
        ));

        $this->auditar(
            $usuarioLogadoId,
            'cliente.editado',
            $atualizado,
            antes: self::instantaneo($atual),
            depois: self::instantaneo($atualizado),
        );

        return $atualizado;
    }

    /** @throws ClienteNaoEncontrado */
    public function remover(int $id, ?int $usuarioLogadoId = null): void
    {
        $cliente = $this->buscar($id); // garante o 404 de domínio antes de remover
        $this->clientes->remover($id);

        // Exclusão é a ação mais sensível e a mais esquecida na auditoria,
        // porque parece "só um UPDATE deletado_em".
        $this->auditar($usuarioLogadoId, 'cliente.excluido', $cliente, antes: self::instantaneo($cliente));
    }

    private function exigirPermissaoDePlano(int $contaId): void
    {
        if ($this->limites === null) {
            return;
        }

        $this->exigirAssinaturaAtiva($contaId);

        if (!$this->limites->podeCriarCliente($contaId)) {
            throw LimiteDoPlanoAtingido::clientes($this->limites->limiteDe($contaId, 'max_clientes'));
        }
    }

    private function exigirAssinaturaAtiva(int $contaId): void
    {
        if ($this->limites !== null && !$this->limites->podeEscrever($contaId)) {
            throw LimiteDoPlanoAtingido::assinaturaInativa();
        }
    }

    /** @return list<Cliente> Lixeira: o que foi excluído e pode voltar. */
    public function lixeira(): array
    {
        return $this->clientes->removidos();
    }

    public function restaurar(int $id, ?int $usuarioLogadoId = null): void
    {
        $this->clientes->restaurar($id);
        $cliente = $this->clientes->buscarPorId($id);

        if ($cliente !== null) {
            // Restaurar é tão sensível quanto excluir: pode reviver algo que
            // deveria continuar fora. Por isso também vai para a auditoria.
            $this->auditar($usuarioLogadoId, 'cliente.restaurado', $cliente, depois: self::instantaneo($cliente));
        }
    }

    /**
     * @param array<string, mixed>|null $antes
     * @param array<string, mixed>|null $depois
     */
    private function auditar(?int $usuarioId, string $acao, Cliente $cliente, ?array $antes = null, ?array $depois = null): void
    {
        $id = $cliente->id();

        if ($id === null) {
            return;
        }

        $this->auditoria->registrar($usuarioId, $acao, 'cliente', $id, $antes, $depois);
    }

    /** @return array<string, mixed> */
    private static function instantaneo(Cliente $cliente): array
    {
        return [
            'nome' => $cliente->nome(),
            'email' => $cliente->email(),
            'telefone' => $cliente->telefone(),
            'status' => $cliente->status()->value,
        ];
    }
}
