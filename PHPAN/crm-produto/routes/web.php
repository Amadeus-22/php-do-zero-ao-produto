<?php

declare(strict_types=1);

use App\Http\Controllers\AnexoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LembreteController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ResetSenhaController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\View;

/** @var Router $router */
/** @var callable(list<class-string>, callable): callable $pipeline */

$router->get('/', static fn (Request $r): Response => Response::redirect('/clientes'));

// Login: sem AuthMiddleware, senão ninguém consegue chegar até ele.
// /health SEM autenticação: monitor externo precisa alcançar sem token.
$router->get('/health', $pipeline([], [HealthController::class, '__invoke']));

$router->get('/login', $pipeline([], [LoginController::class, 'formulario']));
$router->post('/login', $pipeline([CsrfMiddleware::class], [LoginController::class, 'entrar']));
$router->post('/logout', $pipeline([CsrfMiddleware::class], [LoginController::class, 'sair']));

// Reset de senha: público (quem esqueceu a senha não consegue logar).
// A proteção aqui é rate limit + mensagem única, não autenticação.
$router->get('/esqueci-senha', $pipeline([], [ResetSenhaController::class, 'formularioSolicitar']));
$router->post('/esqueci-senha', $pipeline([CsrfMiddleware::class], [ResetSenhaController::class, 'solicitar']));
$router->get('/redefinir-senha', $pipeline([], [ResetSenhaController::class, 'formularioRedefinir']));
$router->post('/redefinir-senha', $pipeline([CsrfMiddleware::class], [ResetSenhaController::class, 'redefinir']));

$router->get('/painel', static fn (Request $r): Response
    => Response::html(View::render('painel', ['titulo' => 'Painel via API'])));

// ORDEM IMPORTA: /clientes/novo antes de /clientes/{id}, senão "novo" vira um id.
$router->get('/clientes', $pipeline([AuthMiddleware::class], [ClienteController::class, 'index']));
$router->get('/clientes/novo', $pipeline([AuthMiddleware::class], [ClienteController::class, 'novo']));
$router->get('/clientes/{id}', $pipeline([AuthMiddleware::class], [ClienteController::class, 'show']));

$router->post('/clientes', $pipeline([AuthMiddleware::class, CsrfMiddleware::class], [ClienteController::class, 'criar']));

// AdminMiddleware DEPOIS de Auth: ele lê $_SESSION['papel'] contando que a sessão exista.
// Lembretes (agenda do CRM)
$router->get('/lembretes', $pipeline([AuthMiddleware::class], [LembreteController::class, 'index']));
$router->post('/clientes/{id}/lembretes', $pipeline([AuthMiddleware::class, CsrfMiddleware::class], [LembreteController::class, 'criar']));
$router->post('/lembretes/{id}/concluir', $pipeline([AuthMiddleware::class, CsrfMiddleware::class], [LembreteController::class, 'concluir']));

// Anexos: upload exige papel de escrita; download exige permissão de leitura.
$router->post('/clientes/{id}/anexos', $pipeline([AuthMiddleware::class, CsrfMiddleware::class], [AnexoController::class, 'enviar']));
$router->get('/anexos/{id}', $pipeline([AuthMiddleware::class], [AnexoController::class, 'baixar']));

// Auditoria: só admin (o Gate decide dentro do controller).
$router->get('/auditoria/{entidade}/{id}', $pipeline([AuthMiddleware::class], [AuditoriaController::class, 'porEntidade']));

$router->post('/clientes/{id}/remover', $pipeline(
    [AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class],
    [ClienteController::class, 'remover'],
));
