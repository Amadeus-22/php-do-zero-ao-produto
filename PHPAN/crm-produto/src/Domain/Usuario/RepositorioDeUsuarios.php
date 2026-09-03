<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

interface RepositorioDeUsuarios
{
    public function salvar(Usuario $usuario): Usuario;

    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorEmail(string $email): ?Usuario;

    public function trocarSenha(int $id, string $novaSenhaHash): void;
}
