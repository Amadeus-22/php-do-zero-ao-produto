<?php

declare(strict_types=1);

namespace Tests\Log;

use App\Log\Logger;
use App\Log\Nivel;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    private string $arquivo = '';

    protected function setUp(): void
    {
        $this->arquivo = sys_get_temp_dir() . '/crm-log-' . bin2hex(random_bytes(4)) . '.jsonl';
    }

    protected function tearDown(): void
    {
        if (is_file($this->arquivo)) {
            unlink($this->arquivo);
        }
    }

    /** @return list<array<string, mixed>> */
    private function linhas(): array
    {
        return array_map(
            static fn (string $l): array => json_decode($l, true),
            array_filter(explode("\n", (string) file_get_contents($this->arquivo))),
        );
    }

    public function testCadaLinhaEhUmJsonIndependente(): void
    {
        $logger = new Logger($this->arquivo);
        $logger->info('primeiro');
        $logger->error('segundo');

        $linhas = $this->linhas();

        self::assertCount(2, $linhas);
        self::assertSame('info', $linhas[0]['nivel']);
        self::assertSame('error', $linhas[1]['nivel']);
    }

    public function testLinhaTemTimestampNivelMensagemEContexto(): void
    {
        (new Logger($this->arquivo))->warning('rate limit acionado', ['ip' => '10.0.0.1']);

        $linha = $this->linhas()[0];

        self::assertArrayHasKey('timestamp', $linha);
        self::assertSame('warning', $linha['nivel']);
        self::assertSame('rate limit acionado', $linha['mensagem']);
        self::assertSame('10.0.0.1', $linha['contexto']['ip']);
    }

    public function testCampoSensivelNaoEhGravado(): void
    {
        (new Logger($this->arquivo))->error('falha no login', [
            'email' => 'ana@exemplo.com',
            'senha' => 'segredo123',
            'token' => 'abc',
        ]);

        $contexto = $this->linhas()[0]['contexto'];

        self::assertSame('ana@exemplo.com', $contexto['email']);
        self::assertSame('[REMOVIDO]', $contexto['senha']);
        self::assertSame('[REMOVIDO]', $contexto['token']);
    }

    public function testLimpezaAlcancaContextoAninhado(): void
    {
        (new Logger($this->arquivo))->error('webhook', ['payload' => ['id' => 1, 'access' => 'tok']]);

        $contexto = $this->linhas()[0]['contexto'];

        self::assertSame(1, $contexto['payload']['id']);
        self::assertSame('[REMOVIDO]', $contexto['payload']['access']);
    }

    public function testTodosOsNiveisPsr3Disponiveis(): void
    {
        $logger = new Logger($this->arquivo);

        foreach (Nivel::cases() as $nivel) {
            $logger->log($nivel, 'linha');
        }

        self::assertSame(
            array_map(static fn (Nivel $n): string => $n->value, Nivel::cases()),
            array_column($this->linhas(), 'nivel'),
        );
    }

    public function testGrepPorNivelFunciona(): void
    {
        $logger = new Logger($this->arquivo);
        $logger->info('ok');
        $logger->error('quebrou');

        $erros = array_filter(
            explode("\n", (string) file_get_contents($this->arquivo)),
            static fn (string $l): bool => str_contains($l, '"nivel":"error"'),
        );

        self::assertCount(1, $erros, 'é para isso que serve o formato JSON Lines');
    }
}
