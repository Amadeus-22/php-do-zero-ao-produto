<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Config\Config;
use App\Http\Request;
use App\Http\Response;

/**
 * Cabeçalhos de segurança. Não impedem um bug, mas reduzem o que um bug
 * consegue causar — defesa em profundidade.
 */
final class SecurityHeaders implements Middleware
{
    /** @return array<string, string> */
    public static function cabecalhos(): array
    {
        $headers = [
            // impede o browser de "adivinhar" o tipo do conteúdo
            'X-Content-Type-Options' => 'nosniff',
            // clickjacking: o painel não pode ser carregado em iframe de terceiro
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // CSP restritiva: começa fechada e abre exceção pontual, nunca o contrário
            'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-ancestors 'none'",
        ];

        // HSTS só em produção com HTTPS: é "pegajoso" no navegador do usuário e,
        // ativado cedo demais, tranca quem tiver problema de certificado.
        if (Config::string('APP_ENV', 'production') === 'production') {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }

    public function handle(Request $request): ?Response
    {
        foreach (self::cabecalhos() as $nome => $valor) {
            if (!headers_sent()) {
                header("{$nome}: {$valor}");
            }
        }

        return null;
    }
}
