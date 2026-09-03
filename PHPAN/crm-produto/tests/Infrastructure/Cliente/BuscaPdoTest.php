<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Cliente;

use App\Domain\Cliente\CriterioDeBusca;
use App\Support\Container;
use Tests\Support\BancoDeTeste;

/**
 * Busca e paginação contra o MySQL de verdade.
 *
 * Existe porque o filtro `q` quebrava só aqui: a query repetia o placeholder
 * `:q` e, com ATTR_EMULATE_PREPARES desligado, o MySQL recusa parâmetro nomeado
 * repetido. Os testes com o duplo em memória passavam — o SQL nem era executado.
 */
final class BuscaPdoTest extends BancoDeTeste
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([['Ana Souza', 'ana@exemplo.com'], ['Bruno Silva', 'bruno@exemplo.com'], ['Carla Silva', 'carla@empresa.com']] as [$n, $e]) {
            Container::clienteService()->criar(['nome' => $n, 'email' => $e]);
        }
    }

    public function testFiltroPorNomeParcial(): void
    {
        $achados = Container::repositorioDeClientes()->buscar(new CriterioDeBusca(q: 'Silva'));

        self::assertCount(2, $achados);
    }

    public function testFiltroTambemBateNoEmail(): void
    {
        self::assertCount(1, Container::repositorioDeClientes()->buscar(new CriterioDeBusca(q: '@empresa.com')));
    }

    public function testContarRespeitaOMesmoFiltro(): void
    {
        $criterio = new CriterioDeBusca(q: 'Silva');

        self::assertSame(2, Container::repositorioDeClientes()->contar($criterio));
    }

    public function testPaginacaoAcontecdeNoSql(): void
    {
        $repo = Container::repositorioDeClientes();

        $pagina1 = $repo->buscar(new CriterioDeBusca(page: 1, perPage: 2));
        $pagina2 = $repo->buscar(new CriterioDeBusca(page: 2, perPage: 2));

        self::assertCount(2, $pagina1);
        self::assertCount(1, $pagina2);
        self::assertNotSame($pagina1[0]->id(), $pagina2[0]->id());
    }

    public function testBuscaIgnoraClienteNaLixeira(): void
    {
        $service = Container::clienteService();
        $id = $service->criar(['nome' => 'Fantasma Silva', 'email' => 'fantasma@exemplo.com'])->id();
        self::assertNotNull($id);
        $service->remover($id);

        self::assertCount(2, Container::repositorioDeClientes()->buscar(new CriterioDeBusca(q: 'Silva')));
    }

    public function testFiltroPorAtivo(): void
    {
        self::assertCount(3, Container::repositorioDeClientes()->buscar(new CriterioDeBusca(ativo: true)));
        self::assertCount(0, Container::repositorioDeClientes()->buscar(new CriterioDeBusca(ativo: false)));
    }
}
