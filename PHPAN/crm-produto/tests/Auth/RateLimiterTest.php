<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Support\RateLimiter;
use Tests\Support\BancoDeTeste;

final class RateLimiterTest extends BancoDeTeste
{
    public function testBloqueiaDepoisDoLimiteNaJanela(): void
    {
        $limiter = new RateLimiter($this->pdo);

        for ($i = 1; $i <= 5; $i++) {
            self::assertFalse($limiter->atingiu('login:ana@exemplo.com:10.0.0.1', 5, 900), "tentativa {$i}");
        }

        self::assertTrue($limiter->atingiu('login:ana@exemplo.com:10.0.0.1', 5, 900), '6ª tentativa deveria bloquear');
    }

    public function testChavesDiferentesNaoSeAfetam(): void
    {
        $limiter = new RateLimiter($this->pdo);

        for ($i = 0; $i < 5; $i++) {
            $limiter->atingiu('login:ana@exemplo.com:10.0.0.1', 5, 900);
        }

        // outro e-mail no MESMO IP continua livre — a chave combina os dois
        self::assertFalse($limiter->atingiu('login:bruno@exemplo.com:10.0.0.1', 5, 900));
        // e o mesmo e-mail de OUTRO IP também
        self::assertFalse($limiter->atingiu('login:ana@exemplo.com:10.0.0.2', 5, 900));
    }

    public function testTentativaAntigaSaiDaJanela(): void
    {
        $limiter = new RateLimiter($this->pdo);

        for ($i = 0; $i < 5; $i++) {
            $limiter->atingiu('login:ana@exemplo.com:10.0.0.1', 5, 900);
        }
        self::assertTrue($limiter->atingiu('login:ana@exemplo.com:10.0.0.1', 5, 900));

        // envelhece as tentativas para além da janela
        $this->pdo->exec('UPDATE tentativas_login SET criado_em = DATE_SUB(NOW(), INTERVAL 20 MINUTE)');

        self::assertFalse($limiter->atingiu('login:ana@exemplo.com:10.0.0.1', 5, 900), 'a janela deveria ter virado');
    }

    public function testLimpezaRemoveRegistroAntigo(): void
    {
        $limiter = new RateLimiter($this->pdo);
        $limiter->atingiu('login:x:1', 5, 900);
        $this->pdo->exec('UPDATE tentativas_login SET criado_em = DATE_SUB(NOW(), INTERVAL 2 DAY)');

        self::assertSame(1, $limiter->limparAntigos(86400));
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM tentativas_login')->fetchColumn());
    }
}
