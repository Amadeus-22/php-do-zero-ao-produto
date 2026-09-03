<?php

// PHPAN · Módulo 3 · Aula 06 — Middleware leve: auth, CSRF, "só admin"
// metadados em aulas.json · a ideia em 06-middleware-leve.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use App\Support\Container;
use App\Support\Csrf;

Container::usar(new RepositorioDeClientesEmMemoria());

titulo('Aula 6 — Middleware leve: auth, CSRF, "só admin"');

secao('O contrato: null segue, Response interrompe');

$_SESSION = [];
$auth = new AuthMiddleware();
$r = $auth->handle(Request::falsa('GET', '/clientes'));
checa('sem sessão, AuthMiddleware INTERROMPE', $r instanceof Response, 'redirect para /login');
checa('e manda para o login', ($r?->headers['Location'] ?? '') === '/login', '');

$_SESSION['usuario_id'] = 7;
checa('com sessão, devolve null e segue', $auth->handle(Request::falsa('GET', '/clientes')) === null, '');

secao('AdminMiddleware: papel decide, e ele ASSUME sessão pronta');

$_SESSION['papel'] = 'vendedor';
$negado = (new AdminMiddleware())->handle(Request::falsa('POST', '/clientes/1/remover'));
checa('vendedor leva 403', $negado?->status === 403, 'acesso restrito');

$_SESSION['papel'] = 'admin';
checa('admin passa', (new AdminMiddleware())->handle(Request::falsa('POST', '/clientes/1/remover')) === null, '');

secao('ARMADILHA — a ordem no array de rota importa');

$_SESSION = []; // visitante: nem usuario_id, nem papel
$cadeiaCorreta = [new AuthMiddleware(), new AdminMiddleware()];
$primeiraResposta = null;
foreach ($cadeiaCorreta as $m) {
    $primeiraResposta = $m->handle(Request::falsa('POST', '/clientes/1/remover'));
    if ($primeiraResposta !== null) {
        break;
    }
}
checa('Auth primeiro: visitante vira redirect para login', ($primeiraResposta?->headers['Location'] ?? '') === '/login', 'resultado claro');

$soAdmin = (new AdminMiddleware())->handle(Request::falsa('POST', '/clientes/1/remover'));
checa('Admin sozinho daria 403 confuso a um visitante', $soAdmin?->status === 403, 'ele lê $_SESSION["papel"] sem garantia de sessão');
nota('Por isso AuthMiddleware SEMPRE vem antes de AdminMiddleware no array.');

secao('CSRF: só age em método que altera estado');

$csrf = new CsrfMiddleware();
checa('GET passa direto', $csrf->handle(Request::falsa('GET', '/clientes')) === null, 'nada a validar');

$_SESSION['csrf_token'] = null;
$token = Csrf::token();
checa('token gerado tem 64 hex (32 bytes)', strlen($token) === 64, 'random_bytes');

$semToken = $csrf->handle(Request::falsa('POST', '/clientes', ['nome' => 'X']));
checa('POST sem token é barrado', $semToken?->status === 419, 'sessão expirada');

$tokenErrado = $csrf->handle(Request::falsa('POST', '/clientes', ['_token' => str_repeat('a', 64)]));
checa('POST com token FORJADO é barrado', $tokenErrado?->status === 419, 'hash_equals recusa');

checa('POST com token válido passa', $csrf->handle(Request::falsa('POST', '/clientes', ['_token' => $token])) === null, '');

secao('Quiz do Módulo 3 — respostas');

$quiz = [
    '1. Service não lê $_POST nem devolve HTML porque' => 'a regra deixaria de ser reaproveitável pela API ou por um comando',
    '2. "E-mail não pode duplicar" mora'               => 'no Service, uma vez só, usado por web e API',
    '3. Validação só no JavaScript basta?'             => 'não — validação de verdade é sempre no servidor',
    '4. Rota que mais fica sem CSRF por engano'        => 'o formulário de remover cliente (altera estado)',
    '5. Ordem Auth x Admin'                            => 'Auth primeiro; Admin só depois que a sessão existe',
    '6. Duplicidade de e-mail no Validator de formato?' => 'não — duplicidade é I/O/negócio; formato fica no Validator',
];
foreach ($quiz as $p => $r) {
    echo "  {$p}\n      -> {$r}\n";
}

fecharAula();
