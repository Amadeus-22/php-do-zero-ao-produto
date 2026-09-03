<?php

declare(strict_types=1);

namespace Tests\Application\Cliente;

use App\Application\Cliente\CadastrarCliente;
use App\Application\Cliente\ListarClientesAtivos;
use App\Domain\Cliente\ClienteInvalido;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use PHPUnit\Framework\TestCase;

final class CadastrarClienteTest extends TestCase
{
    public function testCadastraEAtribuiId(): void
    {
        $caso = new CadastrarCliente(new RepositorioDeClientesEmMemoria());

        $cliente = $caso->executar('Ana Souza', 'ana@exemplo.com');

        self::assertSame(1, $cliente->id());
        self::assertSame('Ana Souza', $cliente->nome());
    }

    public function testEmailInvalidoNaoChegaAoRepositorio(): void
    {
        $repositorio = new RepositorioDeClientesEmMemoria();
        $caso = new CadastrarCliente($repositorio);

        try {
            $caso->executar('Ana Souza', 'invalido');
            self::fail('Deveria ter lançado ClienteInvalido.');
        } catch (ClienteInvalido) {
            self::assertSame([], $repositorio->todosAtivos());
        }
    }

    public function testListarClientesAtivosIgnoraInativos(): void
    {
        $repositorio = new RepositorioDeClientesEmMemoria();
        $cadastrar = new CadastrarCliente($repositorio);
        $listar = new ListarClientesAtivos($repositorio);

        $cadastrar->executar('Ana Souza', 'ana@exemplo.com');
        $bruno = $cadastrar->executar('Bruno Lima', 'bruno@exemplo.com');

        $bruno->desativar();
        $repositorio->salvar($bruno);

        $ativos = $listar->executar();

        self::assertCount(1, $ativos);
        self::assertSame('Ana Souza', $ativos[0]->nome());
    }
}
