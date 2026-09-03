<?php

declare(strict_types=1);

namespace App\Auth;

use App\Domain\Notificacao\RemetenteDeEmail;
use App\Domain\Usuario\RepositorioDeUsuarios;
use DateTimeImmutable;
use PDO;

final readonly class ResetSenhaService
{
    private const VALIDADE = '+1 hour';

    public function __construct(
        private PDO $pdo,
        private RepositorioDeUsuarios $usuarios,
        private RemetenteDeEmail $remetente,
        private TokenService $tokens,
        private string $urlBase,
    ) {
    }

    /**
     * Sempre void: quem chama responde a MESMA mensagem exista ou não o e-mail.
     * Resposta diferente para conta inexistente é a enumeração de usuários mais
     * comum que existe.
     */
    public function solicitar(string $email): void
    {
        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null) {
            return;
        }

        $id = $usuario->id();

        if ($id === null) {
            return;
        }

        // Pedido novo invalida os anteriores: só o link mais recente funciona.
        $this->pdo->prepare('UPDATE resets_senha SET usado_em = NOW() WHERE usuario_id = :id AND usado_em IS NULL')
            ->execute(['id' => $id]);

        $tokenBruto = bin2hex(random_bytes(32)); // random_bytes, nunca uniqid()/rand()

        $this->pdo->prepare(
            'INSERT INTO resets_senha (usuario_id, token_hash, expira_em) VALUES (:uid, :hash, :expira)',
        )->execute([
            'uid' => $id,
            'hash' => hash('sha256', $tokenBruto),
            'expira' => (new DateTimeImmutable(self::VALIDADE))->format('Y-m-d H:i:s'),
        ]);

        $this->remetente->enviar(
            $usuario->email(),
            'Redefinição de senha',
            "Para redefinir sua senha, acesse: {$this->urlBase}/redefinir-senha?token={$tokenBruto}",
        );
    }

    public function redefinir(string $tokenBruto, string $senhaNova): bool
    {
        if (mb_strlen($senhaNova) < 8) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, usuario_id FROM resets_senha
              WHERE token_hash = :hash AND usado_em IS NULL AND expira_em > NOW()',
        );
        $stmt->execute(['hash' => hash('sha256', $tokenBruto)]);
        $reset = $stmt->fetch();

        if ($reset === false) {
            return false;
        }

        $usuarioId = (int) $reset['usuario_id'];

        $this->pdo->beginTransaction();

        try {
            $this->usuarios->trocarSenha($usuarioId, password_hash($senhaNova, PASSWORD_DEFAULT));

            $this->pdo->prepare('UPDATE resets_senha SET usado_em = NOW() WHERE id = :id')
                ->execute(['id' => $reset['id']]);

            // Se a troca foi motivada por invasão, deixar a sessão/token do invasor
            // vivo anula o reset inteiro.
            $this->tokens->revogarTodosDoUsuario($usuarioId);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }

        return true;
    }
}
