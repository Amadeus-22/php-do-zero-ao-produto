<?php

declare(strict_types=1);

namespace App\Auth;

use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;

/**
 * Sessão do PAINEL. Cookie HttpOnly + SameSite; o navegador manda sozinho.
 * A API não usa isto — ela usa Bearer (ver TokenService).
 */
final class Sessao
{
    /** 2 horas: sessão sem expiração é sessão roubada válida para sempre. */
    public const TEMPO_MAXIMO = 2 * 60 * 60;

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.cookie_httponly', '1');   // JS não lê o cookie
        ini_set('session.cookie_samesite', 'Lax'); // mitiga CSRF cross-site
        ini_set('session.use_strict_mode', '1');   // ignora ID inventado pelo cliente

        if (($_SERVER['HTTPS'] ?? '') !== '') {
            ini_set('session.cookie_secure', '1');
        }

        session_start();
    }

    public static function entrar(Usuario $usuario): void
    {
        self::iniciar();

        // Troca o ID no momento do login: um ID "fixado" pelo atacante antes da
        // autenticação deixa de valer. Sem isto, session fixation autentica os dois.
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario->id();
        $_SESSION['papel'] = $usuario->papel()->value;
        $_SESSION['criado_em'] = time();
    }

    public static function usuarioId(): ?int
    {
        self::iniciar();

        if (self::expirou()) {
            self::sair();

            return null;
        }

        $id = $_SESSION['usuario_id'] ?? null;

        return is_int($id) ? $id : null;
    }

    public static function papel(): ?Papel
    {
        return self::usuarioId() === null ? null : Papel::tryFrom((string) ($_SESSION['papel'] ?? ''));
    }

    public static function expirou(): bool
    {
        $criadoEm = $_SESSION['criado_em'] ?? null;

        return is_int($criadoEm) && (time() - $criadoEm) > self::TEMPO_MAXIMO;
    }

    /** Destrói no servidor E expira o cookie no navegador — só o primeiro não basta. */
    public static function sair(): void
    {
        self::iniciar();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name() ?: 'PHPSESSID', '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }
}
