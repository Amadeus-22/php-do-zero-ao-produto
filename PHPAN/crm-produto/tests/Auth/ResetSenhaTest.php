<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Auth\ResetSenhaService;
use App\Auth\TokenService;
use App\Domain\Notificacao\RemetenteDeEmail;
use App\Domain\Usuario\Papel;
use App\Support\Container;
use Tests\Support\BancoDeTeste;

/** Captura os e-mails em memória para o teste inspecionar o link enviado. */
final class RemetenteEspiao implements RemetenteDeEmail
{
    /** @var list<array{destinatario: string, assunto: string, corpo: string}> */
    public array $enviados = [];

    public function enviar(string $destinatario, string $assunto, string $corpo): void
    {
        $this->enviados[] = compact('destinatario', 'assunto', 'corpo');
    }
}

final class ResetSenhaTest extends BancoDeTeste
{
    private RemetenteEspiao $remetente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->remetente = new RemetenteEspiao();
        Container::usarRemetente($this->remetente);
    }

    private function servico(): ResetSenhaService
    {
        return new ResetSenhaService(
            $this->pdo,
            Container::repositorioDeUsuarios(),
            $this->remetente,
            new TokenService($this->pdo),
            'http://localhost:8000',
        );
    }

    private function tokenDoEmail(): string
    {
        preg_match('/token=([0-9a-f]{64})/', $this->remetente->enviados[0]['corpo'], $m);

        return $m[1] ?? '';
    }

    public function testEmailInexistenteNaoRevelaNadaENaoEnvia(): void
    {
        $this->servico()->solicitar('ninguem@exemplo.com');

        self::assertSame([], $this->remetente->enviados);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM resets_senha')->fetchColumn());
        // o controller responde a MESMA mensagem dos dois casos: sem enumeration
    }

    public function testTokenVaiPorEmailEEhGuardadoComoHash(): void
    {
        $this->criarUsuario(Papel::VENDEDOR);
        $this->servico()->solicitar('vendedor@exemplo.com');

        $token = $this->tokenDoEmail();
        self::assertSame(64, strlen($token), 'token de 32 bytes em hex');

        $hashes = $this->pdo->query('SELECT token_hash FROM resets_senha')->fetchAll(\PDO::FETCH_COLUMN);
        self::assertContains(hash('sha256', $token), $hashes);
        self::assertNotContains($token, $hashes, 'token em claro no banco');
    }

    public function testRedefineComTokenValidoEDerrubaSessoes(): void
    {
        $usuario = $this->criarUsuario(Papel::VENDEDOR);
        $id = $usuario->id();
        self::assertNotNull($id);

        // simula sessão de API ativa (a do possível invasor)
        (new TokenService($this->pdo))->emitirPar($id);

        $this->servico()->solicitar('vendedor@exemplo.com');
        self::assertTrue($this->servico()->redefinir($this->tokenDoEmail(), 'nova-senha-forte'));

        $atualizado = Container::repositorioDeUsuarios()->buscarPorId($id);
        self::assertNotNull($atualizado);
        self::assertTrue($atualizado->senhaConfere('nova-senha-forte'));
        self::assertFalse($atualizado->senhaConfere('senha-de-estudo'));

        $ativos = (int) $this->pdo->query('SELECT COUNT(*) FROM tokens WHERE revogado_em IS NULL')->fetchColumn();
        self::assertSame(0, $ativos, 'trocar a senha tem que derrubar os tokens ativos');
    }

    public function testTokenSoServeUmaVez(): void
    {
        $this->criarUsuario(Papel::VENDEDOR);
        $this->servico()->solicitar('vendedor@exemplo.com');
        $token = $this->tokenDoEmail();

        self::assertTrue($this->servico()->redefinir($token, 'nova-senha-forte'));
        self::assertFalse($this->servico()->redefinir($token, 'outra-senha-forte'), 'token reutilizável');
    }

    public function testPedidoNovoInvalidaOAnterior(): void
    {
        $this->criarUsuario(Papel::VENDEDOR);

        $this->servico()->solicitar('vendedor@exemplo.com');
        $primeiro = $this->tokenDoEmail();

        $this->remetente->enviados = [];
        $this->servico()->solicitar('vendedor@exemplo.com');
        $segundo = $this->tokenDoEmail();

        self::assertFalse($this->servico()->redefinir($primeiro, 'nova-senha-forte'), 'link antigo ainda funciona');
        self::assertTrue($this->servico()->redefinir($segundo, 'nova-senha-forte'));
    }

    public function testTokenExpiradoNaoServe(): void
    {
        $this->criarUsuario(Papel::VENDEDOR);
        $this->servico()->solicitar('vendedor@exemplo.com');
        $token = $this->tokenDoEmail();

        $this->pdo->exec('UPDATE resets_senha SET expira_em = DATE_SUB(NOW(), INTERVAL 1 MINUTE)');

        self::assertFalse($this->servico()->redefinir($token, 'nova-senha-forte'));
    }

    public function testSenhaCurtaEhRecusada(): void
    {
        $this->criarUsuario(Papel::VENDEDOR);
        $this->servico()->solicitar('vendedor@exemplo.com');

        self::assertFalse($this->servico()->redefinir($this->tokenDoEmail(), 'curta'));
    }
}
