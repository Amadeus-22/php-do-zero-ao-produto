<?php

declare(strict_types=1);

namespace Tests\Domain\Cliente;

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\ClienteInvalido;
use App\Domain\Cliente\StatusCliente;
use PHPUnit\Framework\TestCase;

final class ClienteTest extends TestCase
{
    public function testNovoComNomeVazioLancaExcecao(): void
    {
        $this->expectException(ClienteInvalido::class);
        $this->expectExceptionMessage('Nome do cliente é obrigatório.');

        Cliente::novo('   ', 'ana@exemplo.com');
    }

    public function testNovoComEmailInvalidoLancaExcecao(): void
    {
        $this->expectException(ClienteInvalido::class);

        Cliente::novo('Ana Souza', 'texto qualquer sem arroba');
    }

    public function testNovoNasceAtivoESemId(): void
    {
        $cliente = Cliente::novo('Ana Souza', 'ana@exemplo.com');

        self::assertNull($cliente->id());
        self::assertTrue($cliente->estaAtivo());
        self::assertSame(StatusCliente::ATIVO, $cliente->status());
    }

    public function testNovoRemoveEspacosDoNome(): void
    {
        $cliente = Cliente::novo('  Ana Souza  ', 'ana@exemplo.com');

        self::assertSame('Ana Souza', $cliente->nome());
    }

    public function testRenomearFunciona(): void
    {
        $cliente = Cliente::novo('Ana Souza', 'ana@exemplo.com');
        $cliente->renomear('Ana S. Souza');

        self::assertSame('Ana S. Souza', $cliente->nome());
    }

    public function testRenomearRevalidaERecusaNomeVazio(): void
    {
        $cliente = Cliente::novo('Ana Souza', 'ana@exemplo.com');

        $this->expectException(ClienteInvalido::class);

        $cliente->renomear('  ');
    }

    public function testDesativarMudaOStatus(): void
    {
        $cliente = Cliente::novo('Ana Souza', 'ana@exemplo.com');
        $cliente->desativar();

        self::assertFalse($cliente->estaAtivo());
        self::assertSame(StatusCliente::INATIVO, $cliente->status());
    }

    public function testReconstituirNaoRevalidaRegrasDeCriacao(): void
    {
        $tresAnosAtras = new \DateTimeImmutable('-3 years');

        $cliente = Cliente::reconstituir(
            id: 42,
            nome: 'Ana Souza',
            email: 'ana@exemplo.com',
            status: StatusCliente::INATIVO,
            criadoEm: $tresAnosAtras,
        );

        self::assertSame(42, $cliente->id());
        self::assertSame($tresAnosAtras, $cliente->criadoEm());
        self::assertFalse($cliente->estaAtivo());
    }
}
