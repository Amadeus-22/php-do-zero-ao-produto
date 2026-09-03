<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Support\Container;

final class WebhookController
{
    public function pagamento(Request $request): Response
    {
        // O corpo CRU é o que foi assinado — reserializar o array mudaria bytes
        // (ordem de chaves, espaços) e a assinatura nunca bateria.
        $payload = $request->corpoCru();
        $assinatura = (string) ($request->server['HTTP_X_ASSINATURA'] ?? '');

        $resultado = Container::webhookPagamento()->processar($payload, $assinatura);

        return Response::json($resultado['body'], $resultado['status']);
    }
}
