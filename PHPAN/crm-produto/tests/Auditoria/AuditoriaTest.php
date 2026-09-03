<?php

declare(strict_types=1);

namespace Tests\Auditoria;

use App\Application\Cliente\ClienteService;
use App\Auditoria\AuditLogger;
use App\Domain\Usuario\Papel;
use App\Infrastructure\Cliente\RepositorioDeClientesPdo;
use Tests\Support\BancoDeTeste;

final class AuditoriaTest extends BancoDeTeste
{
    private function service(): ClienteService
    {
        return new ClienteService(new RepositorioDeClientesPdo($this->pdo), new AuditLogger($this->pdo));
    }

    /** @return list<array<string, mixed>> */
    private function registros(): array
    {
        return $this->pdo->query('SELECT * FROM auditoria ORDER BY id')->fetchAll();
    }

    public function testCriarEditarExcluirDeixamRastro(): void
    {
        $autor = $this->criarUsuario(Papel::ADMIN)->id();
        self::assertNotNull($autor);

        $service = $this->service();
        $cliente = $service->criar(['nome' => 'Ana', 'email' => 'ana@exemplo.com'], $autor);
        $id = $cliente->id();
        self::assertNotNull($id);

        $service->atualizar($id, ['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com'], $autor);
        $service->remover($id, $autor);

        $acoes = array_column($this->registros(), 'acao');

        self::assertSame(['cliente.criado', 'cliente.editado', 'cliente.excluido'], $acoes);
    }

    public function testRastroGuardaQuemFezEOQueMudou(): void
    {
        $autor = $this->criarUsuario(Papel::ADMIN)->id();
        self::assertNotNull($autor);

        $service = $this->service();
        $id = $service->criar(['nome' => 'Ana', 'email' => 'ana@exemplo.com'], $autor)->id();
        self::assertNotNull($id);
        $service->atualizar($id, ['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com'], $autor);

        $edicao = $this->registros()[1];

        self::assertSame($autor, (int) $edicao['usuario_id']);
        self::assertSame('cliente', $edicao['entidade']);
        self::assertSame($id, (int) $edicao['entidade_id']);
        self::assertSame('Ana', json_decode((string) $edicao['dados_antes'], true)['nome']);
        self::assertSame('Ana Souza', json_decode((string) $edicao['dados_depois'], true)['nome']);
    }

    public function testExclusaoEhAuditadaAindaQueSejaSoftDelete(): void
    {
        $service = $this->service();
        $id = $service->criar(['nome' => 'Ana', 'email' => 'ana@exemplo.com'])->id();
        self::assertNotNull($id);
        $service->remover($id);

        $exclusao = $this->registros()[1];

        self::assertSame('cliente.excluido', $exclusao['acao']);
        self::assertNotNull($exclusao['dados_antes'], 'sem o estado anterior o rastro não serve de prova');
    }

    public function testCampoSensivelNuncaEntraNoRastro(): void
    {
        (new AuditLogger($this->pdo))->registrar(
            null,
            'usuario.senha_trocada',
            'usuario',
            1,
            dadosAntes: ['email' => 'ana@exemplo.com', 'senha_hash' => '$2y$deveria-sumir', 'token' => 'abc'],
        );

        $antes = json_decode((string) $this->registros()[0]['dados_antes'], true);

        self::assertArrayHasKey('email', $antes);
        self::assertArrayNotHasKey('senha_hash', $antes);
        self::assertArrayNotHasKey('token', $antes);
    }

    public function testAcaoDoSistemaGravaUsuarioNulo(): void
    {
        $service = $this->service();
        $id = $service->criar(['nome' => 'Job', 'email' => 'job@exemplo.com'])->id();
        self::assertNotNull($id);

        self::assertNull($this->registros()[0]['usuario_id'], 'ação sem usuário logado deve gravar NULL');
    }

    public function testHistoricoPorEntidadeVemDoMaisNovoParaOMaisAntigo(): void
    {
        $service = $this->service();
        $id = $service->criar(['nome' => 'Ana', 'email' => 'ana@exemplo.com'])->id();
        self::assertNotNull($id);
        $service->atualizar($id, ['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com']);

        $historico = (new AuditLogger($this->pdo))->historicoDe('cliente', $id);

        self::assertCount(2, $historico);
        self::assertSame('cliente.editado', $historico[0]['acao']);
    }

    public function testNaoExisteUpdateOuDeleteNaAuditoriaEmTodoOCodigo(): void
    {
        $fontes = shell_exec(
            'grep -rilE "(UPDATE|DELETE FROM) *auditoria" ' . escapeshellarg(dirname(__DIR__, 2) . '/src') . ' 2>/dev/null',
        );

        self::assertSame('', trim((string) $fontes), 'auditoria é append-only: nada de UPDATE/DELETE nela');
    }
}
