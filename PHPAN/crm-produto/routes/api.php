<?php

declare(strict_types=1);

use App\Http\Api\V1\AuthApiController;
use App\Http\Controllers\WebhookController;
use App\Http\Api\V1\ClienteApiController;
use App\Http\Middleware\ExigirPapel;
use App\Http\Middleware\ExigirTokenApi;
use App\Http\Router;

/** @var Router $router */
/** @var callable(list<class-string>, callable): callable $pipeline */

// A API usa PUT/DELETE de verdade — quem chama é fetch, não um <form>.
// Autentica (quem é) e autoriza (o que pode) — nesta ordem, sempre.
$autenticado = static fn (string $acao): array => [ExigirTokenApi::class, new ExigirPapel($acao)];

$router->get('/api/v1/clientes', $pipeline($autenticado('cliente.listar'), [ClienteApiController::class, 'index']));
$router->post('/api/v1/clientes', $pipeline($autenticado('cliente.criar'), [ClienteApiController::class, 'store']));
$router->get('/api/v1/clientes/{id}', $pipeline($autenticado('cliente.ver'), [ClienteApiController::class, 'show']));
$router->put('/api/v1/clientes/{id}', $pipeline($autenticado('cliente.editar'), [ClienteApiController::class, 'update']));
$router->delete('/api/v1/clientes/{id}', $pipeline($autenticado('cliente.excluir'), [ClienteApiController::class, 'destroy']));

// Módulo 6: lixeira, restauração e exportação
$router->get('/api/v1/clientes-lixeira', $pipeline($autenticado('cliente.restaurar'), [ClienteApiController::class, 'lixeira']));
$router->post('/api/v1/clientes/{id}/restaurar', $pipeline($autenticado('cliente.restaurar'), [ClienteApiController::class, 'restaurar']));
$router->get('/api/v1/clientes-exportar', $pipeline($autenticado('cliente.exportar'), [ClienteApiController::class, 'exportar']));

// ── Autenticação (Módulo 5) ──────────────────────────────────────────────────
// A ORDEM do array importa: ExigirTokenApi autentica; ExigirPapel autoriza
// depois, contando que já se saiba quem é o usuário.
$router->post('/api/v1/auth/login', $pipeline([], [AuthApiController::class, 'login']));
$router->post('/api/v1/auth/refresh', $pipeline([], [AuthApiController::class, 'refresh']));
$router->post('/api/v1/auth/logout', $pipeline([ExigirTokenApi::class], [AuthApiController::class, 'logout']));
$router->get('/api/v1/auth/eu', $pipeline([ExigirTokenApi::class], [AuthApiController::class, 'eu']));

// ── Webhook de pagamento (Módulo 8) ──────────────────────────────────────────
// SEM autenticação por token: quem chama é o gateway, não um usuário. A defesa
// é a assinatura HMAC do corpo, verificada dentro do handler.
$router->post('/api/v1/webhooks/pagamento', $pipeline([], [WebhookController::class, 'pagamento']));
