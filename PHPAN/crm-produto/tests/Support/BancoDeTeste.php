<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Config\Config;
use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;
use App\Support\Container;
use App\Support\Database;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Base dos testes de INTEGRAÇÃO: usam o banco de verdade.
 *
 * Testes de unidade (domínio, validação) continuam sem banco e rodam em
 * milissegundos. Estes existem porque autenticação e papéis só são verificáveis
 * de ponta a ponta — um duplo diria "passou" sem provar nada sobre o SQL.
 *
 * Se o banco não estiver de pé, o teste é PULADO, não falha: quebrar a suíte por
 * falta de infraestrutura treina a equipe a ignorar vermelho.
 */
abstract class BancoDeTeste extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->conectarOuPular();

        // ordem inversa das FKs
        $tabelas = [
            'auditoria', 'jobs', 'lembretes', 'anexos', 'tokens', 'resets_senha',
            'tentativas_login', 'eventos_webhook', 'assinaturas', 'planos',
            'atividades', 'contatos', 'clientes', 'usuarios',
        ];

        foreach ($tabelas as $tabela) {
            $this->pdo->exec("DELETE FROM {$tabela}");
        }

        // Plano generoso por padrão: agora que o limite é aplicado de verdade,
        // um teste que cria 3 clientes esbarraria nele sem ter nada a ver com
        // cobrança. Quem testa limite sobrescreve isto no próprio setUp.
        $this->pdo->exec("INSERT INTO planos (codigo, nome, max_clientes, max_usuarios) VALUES ('teste', 'Teste', 1000, 100)");
        $plano = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO assinaturas (conta_id, plano_id, status, renova_em) VALUES (1, {$plano}, 'ativa', DATE_ADD(CURDATE(), INTERVAL 1 MONTH))");

        Container::usar(new \App\Infrastructure\Cliente\RepositorioDeClientesPdo($this->pdo));
        Container::usarUsuarios(new \App\Infrastructure\Usuario\RepositorioDeUsuariosPdo($this->pdo));
    }

    protected function criarUsuario(Papel $papel, string $senha = 'senha-de-estudo'): Usuario
    {
        return Container::repositorioDeUsuarios()->salvar(
            Usuario::novo(ucfirst($papel->value), "{$papel->value}@exemplo.com", $senha, $papel),
        );
    }

    /** Emite um access token válido para o papel pedido. */
    protected function tokenPara(Papel $papel): string
    {
        $id = $this->criarUsuario($papel)->id();
        self::assertNotNull($id);

        return \App\Support\Container::tokenService()->emitirPar($id)['access'];
    }

    private function conectarOuPular(): PDO
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
