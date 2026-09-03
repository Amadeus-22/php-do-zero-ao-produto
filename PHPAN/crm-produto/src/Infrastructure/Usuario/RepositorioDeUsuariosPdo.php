<?php

declare(strict_types=1);

namespace App\Infrastructure\Usuario;

use App\Domain\Usuario\Papel;
use App\Domain\Usuario\RepositorioDeUsuarios;
use App\Domain\Usuario\Usuario;
use PDO;

final readonly class RepositorioDeUsuariosPdo implements RepositorioDeUsuarios
{
    private const CAMPOS = 'id, nome, email, senha_hash, papel';

    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function salvar(Usuario $usuario): Usuario
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, papel) VALUES (:nome, :email, :senha_hash, :papel)',
        );
        $stmt->execute([
            'nome' => $usuario->nome(),
            'email' => $usuario->email(),
            'senha_hash' => $usuario->senhaHash(),
            'papel' => $usuario->papel()->value,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return Usuario::reconstituir($id, $usuario->nome(), $usuario->email(), $usuario->senhaHash(), $usuario->papel());
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::CAMPOS . ' FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $this->hidratar($stmt->fetch());
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::CAMPOS . ' FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => strtolower(trim($email))]);

        return $this->hidratar($stmt->fetch());
    }

    public function trocarSenha(int $id, string $novaSenhaHash): void
    {
        $this->pdo->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id')
            ->execute(['hash' => $novaSenhaHash, 'id' => $id]);
    }

    /** @param array<string, mixed>|false $linha */
    private function hidratar(array|false $linha): ?Usuario
    {
        if ($linha === false) {
            return null;
        }

        return Usuario::reconstituir(
            id: (int) $linha['id'],
            nome: (string) $linha['nome'],
            email: (string) $linha['email'],
            senhaHash: (string) $linha['senha_hash'],
            papel: Papel::from((string) $linha['papel']),
        );
    }
}
