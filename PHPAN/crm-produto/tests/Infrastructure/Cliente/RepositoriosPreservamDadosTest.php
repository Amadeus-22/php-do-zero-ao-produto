<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Cliente;

use App\Config\Config;
use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\RepositorioDeClientes;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use App\Infrastructure\Cliente\RepositorioDeClientesPdo;
use App\Support\Database;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * O mesmo contrato cobrado de TODA implementação.
 *
 * Existe porque um dos repositórios descartava o telefone silenciosamente: o dado
 * entrava, o POST devolvia 201, e o campo voltava null sem erro nenhum. Ao escrever
 * este teste descobri que a OUTRA implementação tinha o mesmo defeito — testar o
 * contrato pega o que testar uma implementação de cada vez deixa passar.
 */
final class RepositoriosPreservamDadosTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function implementacoes(): array
    {
        return ['em memória' => ['memoria'], 'PDO/MySQL' => ['pdo']];
    }

    #[DataProvider('implementacoes')]
    public function testPreservaTodosOsCamposDoCliente(string $tipo): void
    {
        $salvo = $this->criar($tipo)->salvar(Cliente::novo('Ana Souza', $this->email(), '11999990000'));

        self::assertNotNull($salvo->id());
        self::assertSame('Ana Souza', $salvo->nome());
        self::assertSame('11999990000', $salvo->telefone(), "{$tipo}: telefone se perdeu ao salvar");
        self::assertTrue($salvo->estaAtivo());
    }

    #[DataProvider('implementacoes')]
    public function testCamposSobrevivemAoRecarregar(string $tipo): void
    {
        $repo = $this->criar($tipo);
        $id = $repo->salvar(Cliente::novo('Ana Souza', $this->email(), '11999990000'))->id();

        self::assertNotNull($id);
        $recarregado = $repo->buscarPorId($id);

        self::assertNotNull($recarregado);
        self::assertSame('11999990000', $recarregado->telefone(), "{$tipo}: telefone se perdeu ao recarregar");
    }

    #[DataProvider('implementacoes')]
    public function testTelefoneOpcionalContinuaNulo(string $tipo): void
    {
        self::assertNull($this->criar($tipo)->salvar(Cliente::novo('Bruno', $this->email()))->telefone());
    }

    #[DataProvider('implementacoes')]
    public function testRemoverEsconde(string $tipo): void
    {
        $repo = $this->criar($tipo);
        $id = $repo->salvar(Cliente::novo('Ana', $this->email()))->id();

        self::assertNotNull($id);
        $repo->remover($id);

        self::assertNull($repo->buscarPorId($id));
    }

    private function email(): string
    {
        return 'teste-' . bin2hex(random_bytes(6)) . '@exemplo.com';
    }

    private function criar(string $tipo): RepositorioDeClientes
    {
        if ($tipo === 'memoria') {
            return new RepositorioDeClientesEmMemoria();
        }

        return new RepositorioDeClientesPdo($this->pdoOuPular());
    }

    /** Teste de integração: pula (não falha) quando o banco não está de pé. */
    private function pdoOuPular(): PDO
    {
        try {
            Config::carregar();
            $pdo = Database::conexao();
            $pdo->query('SELECT 1');

            return $pdo;
        } catch (Throwable $e) {
            self::markTestSkipped('Banco indisponível: ' . $e->getMessage());
        }
    }
}
