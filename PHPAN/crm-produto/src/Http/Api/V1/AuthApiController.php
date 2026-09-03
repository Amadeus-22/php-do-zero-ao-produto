<?php

declare(strict_types=1);

namespace App\Http\Api\V1;

use App\Auth\LoginPainel;
use App\Http\ApiError;
use App\Http\ApiResponse;
use App\Http\Middleware\ExigirTokenApi;
use App\Http\Request;
use App\Http\Response;
use App\Support\Container;

final class AuthApiController
{
    public function login(Request $request): Response
    {
        $email = $request->texto('email');
        $senha = $request->texto('senha');
        $ip = (string) ($request->server['REMOTE_ADDR'] ?? 'desconhecido');

        $limiter = Container::rateLimiter();
        $chave = "api-login:{$email}:{$ip}";

        if ($limiter->atingiu($chave, LoginPainel::LIMITE_TENTATIVAS, LoginPainel::JANELA_SEGUNDOS)) {
            // Retry-After: sem ele o cliente tenta de novo na hora e piora o problema
            return ApiError::make('rate_limited', 'Muitas tentativas. Tente mais tarde.', 429)
                ->comCabecalho('Retry-After', (string) LoginPainel::JANELA_SEGUNDOS);
        }

        $usuario = Container::repositorioDeUsuarios()->buscarPorEmail($email);

        if ($usuario === null || !$usuario->senhaConfere($senha)) {
            return ApiError::make('unauthorized', 'Credenciais inválidas.', 401);
        }

        $id = $usuario->id();

        if ($id === null) {
            return ApiError::make('server_error', 'Usuário sem identificador.', 500);
        }

        return ApiResponse::ok(Container::tokenService()->emitirPar($id));
    }

    public function refresh(Request $request): Response
    {
        $novoPar = Container::tokenService()->renovar($request->texto('refresh'));

        if ($novoPar === null) {
            return ApiError::make('unauthorized', 'Refresh token inválido ou expirado.', 401);
        }

        return ApiResponse::ok($novoPar);
    }

    public function logout(Request $request): Response
    {
        $usuarioId = ExigirTokenApi::$usuarioId;

        if ($usuarioId === null) {
            return ApiError::make('unauthorized', 'Não autenticado.', 401);
        }

        $revogados = Container::tokenService()->revogarTodosDoUsuario($usuarioId);

        return ApiResponse::ok(['revogados' => $revogados]);
    }

    public function eu(Request $request): Response
    {
        $usuarioId = ExigirTokenApi::$usuarioId;
        $usuario = $usuarioId === null ? null : Container::repositorioDeUsuarios()->buscarPorId($usuarioId);

        if ($usuario === null) {
            return ApiError::make('unauthorized', 'Não autenticado.', 401);
        }

        return ApiResponse::ok([
            'id' => $usuario->id(),
            'nome' => $usuario->nome(),
            'email' => $usuario->email(),
            'papel' => $usuario->papel()->value,
            'pode' => (new \App\Domain\Usuario\Gate())->acoesDe($usuario->papel()),
        ]);
    }
}
