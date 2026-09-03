<?php

declare(strict_types=1);

namespace Tests;

use App\HealthCheck;
use Tests\Support\BancoDeTeste;

final class HealthCheckTest extends BancoDeTeste
{
    public function testComTudoNoLugarRespondeOkE200(): void
    {
        $check = new HealthCheck();
        $status = $check->status();

        self::assertSame('ok', $status['status']);
        self::assertSame('ok', $status['checks']['database']);
        self::assertSame(200, $check->httpStatus());
    }

    public function testChecaDependenciaEnaoApenasOProcesso(): void
    {
        $checks = (new HealthCheck())->status()['checks'];

        // "200 OK" sem checar nada dá falso positivo justamente quando mais importa
        self::assertArrayHasKey('database', $checks);
        self::assertArrayHasKey('disk', $checks);
        self::assertArrayHasKey('queue', $checks);
    }

    public function testFilaAtrasadaAparecéNoStatus(): void
    {
        for ($i = 0; $i < 101; $i++) {
            $this->pdo->exec(
                "INSERT INTO jobs (tipo, payload, disponivel_em) VALUES ('teste', '{}', DATE_SUB(NOW(), INTERVAL 1 HOUR))",
            );
        }

        self::assertSame('atrasada', (new HealthCheck())->status()['checks']['queue']);
        // o site responde normalmente, mas nada está sendo processado —
        // degradação silenciosa que só o health check revela
    }

    public function testNaoVazaDetalheDeInfraestrutura(): void
    {
        $json = json_encode((new HealthCheck())->status());

        self::assertIsString($json);
        foreach (['crm-estudo', '127.0.0.1', 'PDOException', 'root'] as $segredo) {
            self::assertStringNotContainsString($segredo, $json, "o /health vazou: {$segredo}");
        }
    }

    public function testTimestampNoFormatoAtom(): void
    {
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            (new HealthCheck())->status()['timestamp'],
        );
    }
}
