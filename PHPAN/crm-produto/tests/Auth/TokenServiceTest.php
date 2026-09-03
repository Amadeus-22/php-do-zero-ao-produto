<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Auth\TokenService;
use App\Domain\Usuario\Papel;
use Tests\Support\BancoDeTeste;

final class TokenServiceTest extends BancoDeTeste
{
    private function servico(): TokenService
    {
        return new TokenService($this->pdo);
    }

    public function testTokenNuncaEGravadoEmClaroNoBanco(): void
    {
        $id = $this->criarUsuario(Papel::ADMIN)->id();
        self::assertNotNull($id);

        $par = $this->servico()->emitirPar($id);

        $guardados = $this->pdo->query('SELECT token_hash FROM tokens')->fetchAll(\PDO::FETCH_COLUMN);

        self::assertNotContains($par['access'], $guardados, 'o token em claro foi parar no banco');
        self::assertContains(hash('sha256', $par['access']), $guardados);
    }

    public function testAccessValidoIdentificaOUsuario(): void
    {
        $id = $this->criarUsuario(Papel::ADMIN)->id();
        self::assertNotNull($id);

        $par = $this->servico()->emitirPar($id);

        self::assertSame($id, $this->servico()->validarAccess($par['access']));
    }

    public function testTokenInventadoNaoVale(): void
    {
        self::assertNull($this->servico()->validarAccess(bin2hex(random_bytes(32))));
    }

    public function testTokenExpiradoNaoVale(): void
    {
        $id = $this->criarUsuario(Papel::ADMIN)->id();
        self::assertNotNull($id);
        $par = $this->servico()->emitirPar($id);

        // envelhece o token à força
        $this->pdo->exec('UPDATE tokens SET expira_em = DATE_SUB(NOW(), INTERVAL 1 MINUTE)');

        self::assertNull(
            $this->servico()->validarAccess($par['access']),
            'faltou a condição expira_em > NOW() na validação',
        );
    }

    public function testRefreshRotacionaEInvalidaOAntigo(): void
    {
        $id = $this->criarUsuario(Papel::ADMIN)->id();
        self::assertNotNull($id);
        $par = $this->servico()->emitirPar($id);

        $novo = $this->servico()->renovar($par['refresh']);

        self::assertNotNull($novo);
        self::assertNotSame($par['access'], $novo['access']);
        self::assertNull(
            $this->servico()->renovar($par['refresh']),
            'o refresh antigo continuou valendo — sem rotação, um vazamento vale para sempre',
        );
    }

    public function testLogoutRevogaNoServidor(): void
    {
        $id = $this->criarUsuario(Papel::ADMIN)->id();
        self::assertNotNull($id);
        $par = $this->servico()->emitirPar($id);

        self::assertSame(2, $this->servico()->revogarTodosDoUsuario($id));
        self::assertNull($this->servico()->validarAccess($par['access']));
    }
}
