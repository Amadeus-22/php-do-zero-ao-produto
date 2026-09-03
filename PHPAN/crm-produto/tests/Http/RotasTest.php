<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Auditoria\AuditoriaNula;
use App\Http\Kernel;
use App\Http\Request;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use App\Support\Container;
use PHPUnit\Framework\TestCase;

/** Roteamento e proteção do painel web — sem banco. */
final class RotasTest extends TestCase
{
    protected function setUp(): void
    {
        Container::usar(new RepositorioDeClientesEmMemoria());
        Container::usarAuditoria(new AuditoriaNula());
        $_SESSION = [];
    }

    /** Simula usuário logado no painel, sem passar pelo formulário. */
    private function logadoComo(string $papel): void
    {
        $_SESSION['usuario_id'] = 1;
        $_SESSION['papel'] = $papel;
        $_SESSION['criado_em'] = time();
    }

    public function testRotaInexistenteDevolve404(): void
    {
        self::assertSame(404, Kernel::router()->resolver(Request::falsa('GET', '/nao-existe'))->status);
    }

    public function testPainelExigeLoginERedirecionaParaOFormulario(): void
    {
        $r = Kernel::router()->resolver(Request::falsa('GET', '/clientes'));

        self::assertSame(302, $r->status);
        self::assertSame('/login', $r->headers['Location']);
    }

    public function testFormularioDeLoginEhPublico(): void
    {
        $r = Kernel::router()->resolver(Request::falsa('GET', '/login'));

        self::assertSame(200, $r->status);
        self::assertStringContainsString('name="senha"', $r->body);
    }

    /** Ordem das rotas: /clientes/novo não pode cair no handler de {id}. */
    public function testRotaEstaticaVenceRotaComParametro(): void
    {
        $this->logadoComo('vendedor');

        $r = Kernel::router()->resolver(Request::falsa('GET', '/clientes/novo'));

        self::assertSame(200, $r->status);
        self::assertStringContainsString('Novo cliente', $r->body);
    }

    /** Barra final não pode gerar 404 (rtrim no Request). */
    public function testBarraFinalCaiNaMesmaRota(): void
    {
        self::assertSame(
            Kernel::router()->resolver(Request::falsa('GET', '/api/v1/clientes'))->status,
            Kernel::router()->resolver(Request::falsa('GET', '/api/v1/clientes/'))->status,
        );
    }

    public function testPostSemTokenCsrfEhRecusado(): void
    {
        $this->logadoComo('vendedor');

        $r = Kernel::router()->resolver(Request::falsa('POST', '/clientes', [
            'nome' => 'Ana',
            'email' => 'ana@exemplo.com',
        ]));

        self::assertSame(419, $r->status);
    }

    /** AuthMiddleware roda ANTES: visitante vira redirect, não 419 confuso. */
    public function testVisitanteEmRotaDeEscritaVaiParaOLoginAntesDoCsrf(): void
    {
        $r = Kernel::router()->resolver(Request::falsa('POST', '/clientes', ['nome' => 'Ana']));

        self::assertSame(302, $r->status);
        self::assertSame('/login', $r->headers['Location']);
    }

    /** AdminMiddleware vem depois de Auth e barra quem está logado sem ser admin. */
    public function testRemoverExigeAdminNoPainel(): void
    {
        $this->logadoComo('vendedor');

        self::assertSame(403, Kernel::router()->resolver(Request::falsa('POST', '/clientes/1/remover'))->status);
    }

    public function testMetodoNaoRegistradoNaRotaDa404(): void
    {
        self::assertSame(404, Kernel::router()->resolver(Request::falsa('DELETE', '/clientes'))->status);
    }
}
