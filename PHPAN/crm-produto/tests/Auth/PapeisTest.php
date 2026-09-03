<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Domain\Usuario\Gate;
use App\Domain\Usuario\Papel;
use App\Http\Kernel;
use App\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BancoDeTeste;

final class PapeisTest extends BancoDeTeste
{
    /** @return array<string, array{0: Papel, 1: int, 2: int, 3: int}> */
    public static function matriz(): array
    {
        //                        papel            listar criar excluir
        return [
            'admin' => [Papel::ADMIN, 200, 201, 204],
            'vendedor' => [Papel::VENDEDOR, 200, 201, 403],
            'leitura' => [Papel::LEITURA, 200, 403, 403],
        ];
    }

    #[DataProvider('matriz')]
    public function testMatrizDePermissoesNaApi(Papel $papel, int $listar, int $criar, int $excluir): void
    {
        $token = $this->tokenPara($papel);
        $router = Kernel::router();

        self::assertSame($listar, $router->resolver(Request::comToken('GET', '/api/v1/clientes', $token))->status);

        $criacao = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, [
            'nome' => 'Cliente Teste',
            'email' => 'cliente@exemplo.com',
        ]));
        self::assertSame($criar, $criacao->status);

        // cria um alvo por dentro, para o teste de exclusão não depender do papel
        $alvo = \App\Support\Container::clienteService()->criar([
            'nome' => 'Alvo',
            'email' => 'alvo@exemplo.com',
        ])->id();
        self::assertNotNull($alvo);

        self::assertSame(
            $excluir,
            $router->resolver(Request::comToken('DELETE', "/api/v1/clientes/{$alvo}", $token))->status,
        );
    }

    public function testSemTokenTudoEh401(): void
    {
        foreach (['GET', 'POST'] as $metodo) {
            self::assertSame(401, Kernel::router()->resolver(Request::falsa($metodo, '/api/v1/clientes'))->status);
        }
    }

    public function testTokenValidoMasSemPermissaoEh403NaoEh401(): void
    {
        $r = Kernel::router()->resolver(
            Request::comToken('POST', '/api/v1/clientes', $this->tokenPara(Papel::LEITURA), [
                'nome' => 'X',
                'email' => 'x@exemplo.com',
            ]),
        );

        // 401 = "não sei quem você é"; 403 = "sei, e você não pode"
        self::assertSame(403, $r->status);
        self::assertSame('forbidden', json_decode($r->body, true)['error']['code']);
    }

    public function testGateEhAUnicaFonteDaMatriz(): void
    {
        $gate = new Gate();

        self::assertTrue($gate->pode(Papel::LEITURA, 'cliente.listar'));
        self::assertFalse($gate->pode(Papel::LEITURA, 'cliente.criar'));
        self::assertFalse($gate->pode(Papel::VENDEDOR, 'cliente.excluir'));
        self::assertTrue($gate->pode(Papel::ADMIN, 'auditoria.ver'));
        self::assertFalse($gate->pode(Papel::VENDEDOR, 'acao.inexistente'), 'ação desconhecida deve NEGAR');
    }
}
