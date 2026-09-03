<?php

declare(strict_types=1);

namespace App\Auth;

use App\Domain\Usuario\RepositorioDeUsuarios;
use App\Support\RateLimiter;

final readonly class LoginPainel
{
    public const LIMITE_TENTATIVAS = 5;
    public const JANELA_SEGUNDOS = 15 * 60;

    public function __construct(
        private RepositorioDeUsuarios $usuarios,
        private RateLimiter $limiter,
    ) {
    }

    public function autenticar(string $email, string $senha, string $ip): ResultadoDeLogin
    {
        $chave = "login:{$email}:{$ip}";

        if ($this->limiter->atingiu($chave, self::LIMITE_TENTATIVAS, self::JANELA_SEGUNDOS)) {
            return ResultadoDeLogin::bloqueado(self::JANELA_SEGUNDOS);
        }

        $usuario = $this->usuarios->buscarPorEmail($email);

        // Mesma resposta para "não existe" e "senha errada": qualquer diferença
        // vira enumeração de usuários.
        if ($usuario === null || !$usuario->senhaConfere($senha)) {
            return ResultadoDeLogin::credenciaisInvalidas();
        }

        Sessao::entrar($usuario);

        return ResultadoDeLogin::sucesso($usuario);
    }
}
