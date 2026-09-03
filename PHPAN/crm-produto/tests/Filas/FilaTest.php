<?php

declare(strict_types=1);

namespace Tests\Filas;

use App\Filas\JobDispatcher;
use App\Filas\JobHandler;
use App\Filas\Worker;
use App\Log\Logger;
use Tests\Support\BancoDeTeste;

final class FilaTest extends BancoDeTeste
{
    private string $log = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo->exec('DELETE FROM jobs');
        $this->log = sys_get_temp_dir() . '/crm-log-' . bin2hex(random_bytes(4)) . '.jsonl';
    }

    protected function tearDown(): void
    {
        if (is_file($this->log)) {
            unlink($this->log);
        }
    }

    /** @param array<string, JobHandler> $handlers */
    private function worker(array $handlers): Worker
    {
        return new Worker($this->pdo, $handlers, new Logger($this->log));
    }

    /** @return array<string, mixed> */
    private function job(): array
    {
        return (array) $this->pdo->query('SELECT * FROM jobs ORDER BY id DESC LIMIT 1')->fetch();
    }

    public function testDespacharSoRegistraAIntencao(): void
    {
        (new JobDispatcher($this->pdo))->despachar('enviar_email_boas_vindas', ['cliente_id' => 7]);

        $job = $this->job();

        self::assertSame('pendente', $job['status']);
        self::assertSame(0, (int) $job['tentativas']);
        self::assertSame(7, json_decode((string) $job['payload'], true)['cliente_id']);
    }

    public function testWorkerProcessaEConclui(): void
    {
        (new JobDispatcher($this->pdo))->despachar('teste', ['cliente_id' => 3]);
        $handler = new HandlerEspiao();

        self::assertTrue($this->worker(['teste' => $handler])->processarProximo());
        self::assertSame(1, $handler->execucoes);
        self::assertSame(3, $handler->payloads[0]['cliente_id']);
        self::assertSame('concluido', $this->job()['status']);
    }

    public function testFilaVaziaNaoQuebra(): void
    {
        self::assertFalse($this->worker([])->processarProximo());
    }

    public function testFalhaAgendaRetryComBackoff(): void
    {
        (new JobDispatcher($this->pdo))->despachar('quebra', []);

        $this->worker(['quebra' => new HandlerEspiao('SMTP fora do ar')])->processarProximo();

        $job = $this->job();
        self::assertSame('pendente', $job['status'], 'deve voltar para a fila, não morrer na 1ª falha');
        self::assertSame(1, (int) $job['tentativas']);
        self::assertStringContainsString('SMTP', (string) $job['erro']);
        self::assertGreaterThan(date('Y-m-d H:i:s'), (string) $job['disponivel_em'], 'backoff: só volta no futuro');
    }

    public function testJobNaoEhPegoAntesDoBackoffVencer(): void
    {
        (new JobDispatcher($this->pdo))->despachar('quebra', []);
        $worker = $this->worker(['quebra' => new HandlerEspiao('falhou')]);
        $worker->processarProximo();

        self::assertFalse($worker->processarProximo(), 'o job reagendado não pode ser pego já');
    }

    public function testDepoisDeCincoTentativasVaiParaDeadLetter(): void
    {
        (new JobDispatcher($this->pdo))->despachar('quebra', []);
        $worker = $this->worker(['quebra' => new HandlerEspiao('sempre falha')]);

        for ($i = 0; $i < Worker::MAX_TENTATIVAS; $i++) {
            $this->pdo->exec('UPDATE jobs SET disponivel_em = NOW()'); // encurta o backoff no teste
            $worker->processarProximo();
        }

        $job = $this->job();
        self::assertSame('falhou', $job['status'], 'sem limite, ele retentaria para sempre');
        self::assertSame(Worker::MAX_TENTATIVAS, (int) $job['tentativas']);
    }

    public function testHandlerDesconhecidoNaoTravaOWorker(): void
    {
        (new JobDispatcher($this->pdo))->despachar('tipo_que_nao_existe', []);

        self::assertTrue($this->worker([])->processarProximo());
        self::assertSame('pendente', $this->job()['status']); // reagendado, worker segue vivo
    }

    public function testJobEmProcessamentoNaoEhPegoPorOutroWorker(): void
    {
        (new JobDispatcher($this->pdo))->despachar('lento', []);
        $this->pdo->exec("UPDATE jobs SET status = 'processando'");

        self::assertFalse($this->worker([])->processarProximo(), 'só status pendente é elegível');
    }
}
