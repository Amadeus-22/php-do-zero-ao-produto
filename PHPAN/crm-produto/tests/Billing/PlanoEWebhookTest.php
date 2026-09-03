<?php

declare(strict_types=1);

namespace Tests\Billing;

use App\Billing\AssinaturaService;
use App\Billing\PlanLimiter;
use App\Billing\WebhookPagamento;
use App\Log\Logger;
use App\Support\Container;
use Tests\Support\BancoDeTeste;

final class PlanoEWebhookTest extends BancoDeTeste
{
    private const SEGREDO = 'segredo-de-teste';

    private int $assinaturaId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['eventos_webhook', 'assinaturas', 'planos'] as $t) {
            $this->pdo->exec("DELETE FROM {$t}");
        }

        $this->pdo->exec("INSERT INTO planos (codigo, nome, max_clientes, max_usuarios) VALUES ('free', 'Free', 2, 1)");
        $planoId = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO assinaturas (conta_id, plano_id, status, renova_em) VALUES (1, {$planoId}, 'ativa', DATE_ADD(CURDATE(), INTERVAL 1 MONTH))");
        $this->assinaturaId = (int) $this->pdo->lastInsertId();
    }

    private function limiter(): PlanLimiter
    {
        return new PlanLimiter($this->pdo);
    }

    private function webhook(): WebhookPagamento
    {
        return new WebhookPagamento(
            $this->pdo,
            new AssinaturaService($this->pdo),
            self::SEGREDO,
            new Logger(sys_get_temp_dir() . '/teste-webhook.jsonl'),
        );
    }

    /** @param array<string, mixed> $evento */
    private function enviar(array $evento, ?string $assinatura = null): array
    {
        $payload = json_encode($evento, JSON_THROW_ON_ERROR);

        return $this->webhook()->processar($payload, $assinatura ?? hash_hmac('sha256', $payload, self::SEGREDO));
    }

    // ── limites ──────────────────────────────────────────────────────────────

    public function testDentroDoLimitePodeCriar(): void
    {
        self::assertTrue($this->limiter()->podeCriarCliente(1));
    }

    public function testNoLimiteBloqueia(): void
    {
        Container::clienteService()->criar(['nome' => 'A', 'email' => 'a@exemplo.com']);
        Container::clienteService()->criar(['nome' => 'B', 'email' => 'b@exemplo.com']);

        self::assertFalse($this->limiter()->podeCriarCliente(1), 'plano free permite 2');
    }

    public function testSemAssinaturaAtivaOLimiteEhZero(): void
    {
        $this->pdo->exec('DELETE FROM assinaturas');

        // sem assinatura = zero acesso, não acesso ilimitado
        self::assertSame(0, $this->limiter()->limiteDe(1, 'max_clientes'));
        self::assertFalse($this->limiter()->podeCriarCliente(1));
    }

    public function testColunaDeLimiteNaoAceitaValorArbitrario(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->limiter()->limiteDe(1, 'max_clientes; DROP TABLE planos');
    }

    public function testAtrasoRecenteAindaPermiteEscrever(): void
    {
        (new AssinaturaService($this->pdo))->marcarAtrasada($this->assinaturaId);

        self::assertTrue($this->limiter()->podeEscrever(1), 'grace period evita cancelamento por atrito');
    }

    public function testAtrasoAlemDoGracePeriodBloqueia(): void
    {
        (new AssinaturaService($this->pdo))->marcarAtrasada($this->assinaturaId);
        $this->pdo->exec('UPDATE assinaturas SET atrasada_desde = DATE_SUB(CURDATE(), INTERVAL 10 DAY)');

        self::assertFalse($this->limiter()->podeEscrever(1));
    }

    public function testCanceladaBloqueiaSempre(): void
    {
        (new AssinaturaService($this->pdo))->cancelar($this->assinaturaId);

        self::assertFalse($this->limiter()->podeEscrever(1));
    }

    // ── webhook ──────────────────────────────────────────────────────────────

    public function testAssinaturaInvalidaEhRecusada(): void
    {
        $r = $this->enviar(['id' => 'evt_1', 'type' => 'payment.succeeded', 'data' => ['assinatura_id' => $this->assinaturaId]], 'hmac-forjado');

        self::assertSame(401, $r['status']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM eventos_webhook')->fetchColumn());
    }

    public function testEventoValidoAtivaAAssinatura(): void
    {
        $this->pdo->exec("UPDATE assinaturas SET status = 'atrasada'");

        $r = $this->enviar(['id' => 'evt_2', 'type' => 'payment.succeeded', 'data' => ['assinatura_id' => $this->assinaturaId]]);

        self::assertSame(200, $r['status']);
        self::assertSame('ativa', $this->pdo->query('SELECT status FROM assinaturas')->fetchColumn());
    }

    public function testMesmoEventoDuasVezesProcessaUmaVezSo(): void
    {
        $evento = ['id' => 'evt_3', 'type' => 'payment.failed', 'data' => ['assinatura_id' => $this->assinaturaId]];

        $primeira = $this->enviar($evento);
        $segunda = $this->enviar($evento);

        self::assertSame('processado', $primeira['body']['status']);
        self::assertSame('ja_processado', $segunda['body']['status']);
        self::assertSame(200, $segunda['status'], 'repetido não é erro: o gateway entregou certo');
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM eventos_webhook')->fetchColumn());
    }

    public function testPayloadInvalidoDa400(): void
    {
        self::assertSame(400, $this->webhook()->processar('{isso não é json', hash_hmac('sha256', '{isso não é json', self::SEGREDO))['status']);
        self::assertSame(400, $this->enviar(['type' => 'payment.succeeded'])['status'], 'sem id não dá para garantir idempotência');
    }

    public function testEventoDesconhecidoRespondeOkSemQuebrar(): void
    {
        $r = $this->enviar(['id' => 'evt_4', 'type' => 'invoice.viewed', 'data' => []]);

        // 2xx: fora disso o gateway reenvia agressivamente para sempre
        self::assertSame(200, $r['status']);
    }

    public function testPagamentoFalhadoMarcaAtrasada(): void
    {
        $this->enviar(['id' => 'evt_5', 'type' => 'payment.failed', 'data' => ['assinatura_id' => $this->assinaturaId]]);

        self::assertSame('atrasada', $this->pdo->query('SELECT status FROM assinaturas')->fetchColumn());
        self::assertNotNull($this->pdo->query('SELECT atrasada_desde FROM assinaturas')->fetchColumn());
    }
}
