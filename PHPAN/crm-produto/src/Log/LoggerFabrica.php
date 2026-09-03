<?php

declare(strict_types=1);

namespace App\Log;

use App\Auth\Sessao;

final class LoggerFabrica
{
    private static ?string $requestId = null;

    public static function criar(): Logger
    {
        return new Logger(dirname(__DIR__, 2) . '/var/logs/app.jsonl');
    }

    /** Correlaciona todas as linhas de uma mesma requisição. */
    public static function requestId(): string
    {
        return self::$requestId ??= bin2hex(random_bytes(8));
    }

    /**
     * Contexto que toda linha deveria ter: sem request_id nem usuario_id, um
     * "erro ao processar" no meio de 500 linhas parecidas não ajuda ninguém.
     *
     * @return array<string, mixed>
     */
    public static function contextoBase(): array
    {
        return [
            'request_id' => self::requestId(),
            'usuario_id' => PHP_SAPI === 'cli' ? null : Sessao::usuarioId(),
        ];
    }
}
