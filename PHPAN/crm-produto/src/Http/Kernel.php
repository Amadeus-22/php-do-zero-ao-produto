<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Monta o Router com as rotas do projeto. Fica separado do front controller
 * para que os testes consigam resolver uma rota sem subir servidor nenhum.
 */
final class Kernel
{
    public static function router(): Router
    {
        $router = new Router();

        /**
         * Envolve o handler na cadeia de middleware.
         * Devolver Response em qualquer middleware PARA a cadeia ali.
         *
         * Aceita class-string (middleware sem estado) ou instância já construída
         * (quando o middleware precisa de parâmetro, como ExigirPapel('cliente.excluir')).
         *
         * @param list<class-string<Middleware\Middleware>|Middleware\Middleware> $middlewares
         * @param array{0:class-string,1:string} $handler
         */
        $pipeline = static function (array $middlewares, array $handler): callable {
            return static function (Request $request, string ...$params) use ($middlewares, $handler): Response {
                // cabeçalhos de segurança em TODA rota, sem depender de lembrar
                array_unshift($middlewares, Middleware\SecurityHeaders::class);

                foreach ($middlewares as $middleware) {
                    $instancia = is_string($middleware) ? new $middleware() : $middleware;
                    $resposta = $instancia->handle($request);

                    if ($resposta !== null) {
                        return $resposta;
                    }
                }

                [$classe, $acao] = $handler;

                return (new $classe())->{$acao}($request, ...$params);
            };
        };

        require dirname(__DIR__, 2) . '/routes/web.php';
        require dirname(__DIR__, 2) . '/routes/api.php';

        return $router;
    }
}
