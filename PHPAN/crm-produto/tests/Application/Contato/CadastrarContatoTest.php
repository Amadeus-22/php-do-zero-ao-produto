<?php

declare(strict_types=1);

namespace Tests\Application\Contato;

use App\Application\Cliente\CadastrarCliente;
use App\Application\Contato\CadastrarContato;
use App\Domain\Cliente\ClienteNaoEncontrado;
use App\Domain\Contato\CanalPreferido;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use App\Infrastructure\Contato\RepositorioDeContatosEmMemoria;
use PHPUnit\Framework\TestCase;

final class CadastrarContatoTest extends TestCase
{
    public function testNaoCadastraContatoParaClienteInexistente(): void
    {
        $caso = new CadastrarContato(
            new RepositorioDeClientesEmMemoria(),
            new RepositorioDeContatosEmMemoria(),
        );

        $this->expectException(ClienteNaoEncontrado::class);
        $this->expectExceptionMessage('Cliente com ID 99 não foi encontrado.');

        $caso->executar(99, 'Bruno', 'bruno@exemplo.com', CanalPreferido::EMAIL);
    }

    public function testCadastraContatoDeClienteExistente(): void
    {
        $clientes = new RepositorioDeClientesEmMemoria();
        $contatos = new RepositorioDeContatosEmMemoria();

        $cliente = (new CadastrarCliente($clientes))->executar('Ana Souza', 'ana@exemplo.com');
        $clienteId = $cliente->id();
        self::assertNotNull($clienteId);

        $contato = (new CadastrarContato($clientes, $contatos))
            ->executar($clienteId, 'Bruno', 'bruno@exemplo.com', CanalPreferido::WHATSAPP);

        self::assertSame(1, $contato->id());
        self::assertSame($clienteId, $contato->clienteId());
        self::assertCount(1, $contatos->doCliente($clienteId));
    }
}
