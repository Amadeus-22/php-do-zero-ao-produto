<?php

declare(strict_types=1);

namespace Tests\Billing;

use App\Billing\LimiteDoPlanoAtingido;
use App\Domain\Usuario\Papel;
use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;
use Tests\Support\BancoDeTeste;

/**
 * O limite de plano APLICADO, não só existindo.
 *
 * Este teste existe porque o PlanLimiter ficou pronto e nunca foi chamado: a
 * verificação da aula conferia que o método existia e que o controller não
 * hardcodava número — passava verde com o limite desligado. Aqui o critério é
 * o comportamento: criar acima do teto tem que falhar.
 */
final class LimiteAplicadoTest extends BancoDeTeste
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['eventos_webhook', 'assinaturas', 'planos'] as $t) {
            $this->pdo->exec("DELETE FROM {$t}");
        }

        $this->pdo->exec("INSERT INTO planos (codigo, nome, max_clientes, max_usuarios) VALUES ('free', 'Free', 2, 1)");
        $plano = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO assinaturas (conta_id, plano_id, status, renova_em) VALUES (1, {$plano}, 'ativa', DATE_ADD(CURDATE(), INTERVAL 1 MONTH))");
    }

    public function testServiceBloqueiaAcimaDoLimite(): void
    {
        $service = Container::clienteService();
        $service->criar(['nome' => 'A', 'email' => 'a@exemplo.com']);
        $service->criar(['nome' => 'B', 'email' => 'b@exemplo.com']);

        $this->expectException(LimiteDoPlanoAtingido::class);

        $service->criar(['nome' => 'C', 'email' => 'c@exemplo.com']);
    }

    public function testApiDevolve403ComCodigoProprio(): void
    {
        $token = $this->tokenPara(Papel::ADMIN);
        $router = Kernel::router();

        foreach (['a', 'b'] as $letra) {
            $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, [
                'nome' => strtoupper($letra),
                'email' => "{$letra}@exemplo.com",
            ]));
        }

        $r = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, [
            'nome' => 'C',
            'email' => 'c@exemplo.com',
        ]));

        self::assertSame(403, $r->status, 'papel permite, plano não');
        self::assertSame('plan_limit_reached', json_decode($r->body, true)['error']['code']);
    }

    public function testAssinaturaVencidaBloqueiaEscrita(): void
    {
        $this->pdo->exec("UPDATE assinaturas SET status = 'atrasada', atrasada_desde = DATE_SUB(CURDATE(), INTERVAL 30 DAY)");

        $this->expectException(LimiteDoPlanoAtingido::class);

        Container::clienteService()->criar(['nome' => 'A', 'email' => 'a@exemplo.com']);
    }

    public function testUpgradeDoPlanoDestrava(): void
    {
        $service = Container::clienteService();
        $service->criar(['nome' => 'A', 'email' => 'a@exemplo.com']);
        $service->criar(['nome' => 'B', 'email' => 'b@exemplo.com']);

        $this->pdo->exec("INSERT INTO planos (codigo, nome, max_clientes, max_usuarios) VALUES ('pro', 'Pro', 100, 10)");
        $pro = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("UPDATE assinaturas SET plano_id = {$pro}");

        $terceiro = $service->criar(['nome' => 'C', 'email' => 'c@exemplo.com']);

        self::assertNotNull($terceiro->id(), 'upgrade destrava sem tocar em código');
    }
}
