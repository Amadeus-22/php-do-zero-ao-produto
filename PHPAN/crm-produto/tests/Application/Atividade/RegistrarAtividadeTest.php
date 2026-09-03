<?php

declare(strict_types=1);

namespace Tests\Application\Atividade;

use App\Application\Atividade\RegistrarAtividade;
use App\Application\Cliente\CadastrarCliente;
use App\Domain\Atividade\AtividadeInvalida;
use App\Domain\Atividade\TipoAtividade;
use App\Infrastructure\Atividade\RepositorioDeAtividadesEmMemoria;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use PHPUnit\Framework\TestCase;

final class RegistrarAtividadeTest extends TestCase
{
    public function testNaoRegistraParaClienteInexistente(): void
    {
        $caso = new RegistrarAtividade(
            new RepositorioDeClientesEmMemoria(),
            new RepositorioDeAtividadesEmMemoria(),
        );

        $this->expectException(AtividadeInvalida::class);

        $caso->executar(404, TipoAtividade::LIGACAO, 'Ligação de retorno');
    }

    public function testRegistraAtividadeDeClienteExistente(): void
    {
        $clientes = new RepositorioDeClientesEmMemoria();
        $atividades = new RepositorioDeAtividadesEmMemoria();

        $cliente = (new CadastrarCliente($clientes))->executar('Ana Souza', 'ana@exemplo.com');
        $clienteId = $cliente->id();
        self::assertNotNull($clienteId);

        $atividade = (new RegistrarAtividade($clientes, $atividades))
            ->executar($clienteId, TipoAtividade::NOTA, 'Cliente pediu proposta revisada');

        self::assertSame(1, $atividade->id());
        self::assertCount(1, $atividades->doCliente($clienteId));
    }
}
