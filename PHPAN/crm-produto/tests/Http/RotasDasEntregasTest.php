<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Domain\Usuario\Papel;
use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;
use App\Support\Csrf;
use Tests\Support\BancoDeTeste;

/**
 * As rotas que as "Entregas da aula" pediam e que faltavam: reset de senha,
 * auditoria, anexos e lembretes. Serviço pronto sem rota não é entrega feita —
 * o usuário não alcança.
 */
final class RotasDasEntregasTest extends BancoDeTeste
{
    private function logadoComo(Papel $papel): int
    {
        $usuario = $this->criarUsuario($papel);
        $id = $usuario->id();
        self::assertNotNull($id);

        $_SESSION['usuario_id'] = $id;
        $_SESSION['papel'] = $papel->value;
        $_SESSION['criado_em'] = time();

        return $id;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    // ── reset de senha (M5/04) ───────────────────────────────────────────────

    public function testFormularioDeEsqueciSenhaEhPublico(): void
    {
        $r = Kernel::router()->resolver(Request::falsa('GET', '/esqueci-senha'));

        self::assertSame(200, $r->status, 'quem esqueceu a senha não consegue logar');
        self::assertStringContainsString('name="email"', $r->body);
    }

    public function testSolicitarRespondeIgualParaEmailQueExisteEQueNaoExiste(): void
    {
        $this->criarUsuario(Papel::VENDEDOR);
        $token = Csrf::token();

        $existe = Kernel::router()->resolver(Request::falsa('POST', '/esqueci-senha', ['_token' => $token, 'email' => 'vendedor@exemplo.com']));
        $naoExiste = Kernel::router()->resolver(Request::falsa('POST', '/esqueci-senha', ['_token' => $token, 'email' => 'ninguem@exemplo.com']));

        // Mesma resposta byte a byte: é isso que impede enumerar contas.
        self::assertSame($existe->status, $naoExiste->status);
        self::assertSame($existe->body, $naoExiste->body);
    }

    public function testRedefinirComTokenInvalidoDa400(): void
    {
        $r = Kernel::router()->resolver(Request::falsa('POST', '/redefinir-senha', [
            '_token' => Csrf::token(),
            'token' => str_repeat('a', 64),
            'senha' => 'senha-nova-123',
        ]));

        self::assertSame(400, $r->status);
    }

    // ── auditoria (M5/06) ────────────────────────────────────────────────────

    public function testAuditoriaSoParaAdmin(): void
    {
        $this->logadoComo(Papel::VENDEDOR);

        self::assertSame(403, Kernel::router()->resolver(Request::falsa('GET', '/auditoria/cliente/1'))->status);
    }

    public function testAdminVeOHistorico(): void
    {
        $this->logadoComo(Papel::ADMIN);
        $id = Container::clienteService()->criar(['nome' => 'Ana', 'email' => 'ana@exemplo.com'])->id();
        self::assertNotNull($id);

        $r = Kernel::router()->resolver(Request::falsa('GET', "/auditoria/cliente/{$id}"));

        self::assertSame(200, $r->status);
        self::assertStringContainsString('cliente.criado', $r->body);
    }

    // ── lembretes (M6/04) ────────────────────────────────────────────────────

    public function testTelaDeLembretesExigeLogin(): void
    {
        self::assertSame(302, Kernel::router()->resolver(Request::falsa('GET', '/lembretes'))->status);
    }

    public function testCriarLembreteParaUmClienteEVerNaLista(): void
    {
        $this->logadoComo(Papel::VENDEDOR);
        $clienteId = Container::clienteService()->criar(['nome' => 'Ana', 'email' => 'ana@exemplo.com'])->id();
        self::assertNotNull($clienteId);

        $criacao = Kernel::router()->resolver(Request::falsa('POST', "/clientes/{$clienteId}/lembretes", [
            '_token' => Csrf::token(),
            'mensagem' => 'Ligar para fechar a proposta',
            'vence_em' => (new \DateTimeImmutable('+2 days'))->format('Y-m-d\TH:i'),
        ]));

        self::assertSame(302, $criacao->status);

        $lista = Kernel::router()->resolver(Request::falsa('GET', '/lembretes'));

        self::assertStringContainsString('Ligar para fechar a proposta', $lista->body);
    }

    public function testConcluirLembreteAlheioNaoFazNada(): void
    {
        $dono = $this->logadoComo(Papel::VENDEDOR);
        $clienteId = Container::clienteService()->criar(['nome' => 'Ana', 'email' => 'ana@exemplo.com'])->id();
        self::assertNotNull($clienteId);

        $lembreteId = Container::lembreteService()->criar($dono, $clienteId, 'Do vendedor', new \DateTimeImmutable('+1 day'));

        // outro usuário tenta concluir
        $_SESSION['usuario_id'] = $dono + 999;
        Kernel::router()->resolver(Request::falsa('POST', "/lembretes/{$lembreteId}/concluir", ['_token' => Csrf::token()]));

        $status = $this->pdo->query("SELECT status FROM lembretes WHERE id = {$lembreteId}")->fetchColumn();
        self::assertSame('pendente', $status, 'o WHERE usuario_id protege mesmo adivinhando o id');
    }

    // ── anexos (M6/01) ───────────────────────────────────────────────────────

    public function testDownloadDeAnexoExigeLogin(): void
    {
        self::assertSame(302, Kernel::router()->resolver(Request::falsa('GET', '/anexos/1'))->status);
    }

    public function testAnexoInexistenteDa404(): void
    {
        $this->logadoComo(Papel::LEITURA);

        self::assertSame(404, Kernel::router()->resolver(Request::falsa('GET', '/anexos/999'))->status);
    }

    public function testUploadExigePapelDeEscrita(): void
    {
        $this->logadoComo(Papel::LEITURA);
        $clienteId = Container::clienteService()->criar(['nome' => 'Ana', 'email' => 'ana@exemplo.com'])->id();
        self::assertNotNull($clienteId);

        $r = Kernel::router()->resolver(Request::falsa('POST', "/clientes/{$clienteId}/anexos", ['_token' => Csrf::token()]));

        self::assertSame(403, $r->status, 'leitura não anexa');
    }
}
