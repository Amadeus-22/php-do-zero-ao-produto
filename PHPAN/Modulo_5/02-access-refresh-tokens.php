<?php

// PHPAN · Módulo 5 · Aula 02 — Access token + refresh token
// metadados em aulas.json · a ideia em 02-access-refresh-tokens.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;
use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;

$pdo = bancoDaAula();
$tokens = Container::tokenService();

$usuario = Container::repositorioDeUsuarios()->salvar(
    Usuario::novo('Ana Admin', 'admin@exemplo.com', 'senha-de-estudo', Papel::ADMIN),
);
$id = (int) $usuario->id();

titulo('Aula 2 — Access token + refresh token');

secao('Por que DOIS tokens');

printf("  %-10s %-10s %s\n", 'access', '15 min', 'vai em toda requisição; se vazar, janela pequena');
printf("  %-10s %-10s %s\n", 'refresh', '30 dias', 'só serve para pedir um access novo');

$par = $tokens->emitirPar($id);
checa('login emite os dois', isset($par['access'], $par['refresh']), '');
checa('gerados com random_bytes(32)', strlen($par['access']) === 64, '64 chars hex');

secao('REGRA DE OURO: o banco nunca vê o token em claro');

$guardados = $pdo->query('SELECT token_hash FROM tokens')->fetchAll(PDO::FETCH_COLUMN);

checa('o token em claro NÃO está no banco', !in_array($par['access'], $guardados, true), '');
checa('o que está guardado é o sha256', in_array(hash('sha256', $par['access']), $guardados, true), '');
nota('Se o banco vazar, os hashes não são reutilizáveis como credencial —');
nota('mesma lógica de senha com password_hash.');

secao('Validação exige as TRÊS condições');

checa('token válido identifica o usuário', $tokens->validarAccess($par['access']) === $id, "usuario_id={$id}");
checa('token inventado não vale', $tokens->validarAccess(bin2hex(random_bytes(32))) === null, 'hash não bate');

$pdo->exec("UPDATE tokens SET expira_em = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE tipo = 'access'");
checa('token EXPIRADO não vale', $tokens->validarAccess($par['access']) === null, 'faltando expira_em > NOW() ele valeria');

$novoPar = $tokens->emitirPar($id);
$pdo->exec("UPDATE tokens SET revogado_em = NOW() WHERE token_hash = '" . hash('sha256', $novoPar['access']) . "'");
checa('token REVOGADO não vale', $tokens->validarAccess($novoPar['access']) === null, 'revogado_em IS NULL');

secao('ROTAÇÃO: o refresh usado morre');

$par = $tokens->emitirPar($id);
$renovado = $tokens->renovar($par['refresh']);

checa('renovar devolve um par novo', $renovado !== null && $renovado['access'] !== $par['access'], '');
checa('o refresh ANTIGO deixa de valer', $tokens->renovar($par['refresh']) === null, 'foi revogado no uso');
nota('Sem rotação, um refresh vazado uma vez valeria pelos 30 dias inteiros.');
nota('Com rotação, o estrago fica limitado a uma única janela.');

secao('Logout de verdade acontece no SERVIDOR');

$par = $tokens->emitirPar($id);
$revogados = $tokens->revogarTodosDoUsuario($id);

checa('logout revoga os tokens ativos', $revogados >= 2, "{$revogados} revogado(s)");
checa('o access não vale mais', $tokens->validarAccess($par['access']) === null, '');
nota('"Logout" que só apaga o token no cliente deixa ele válido no servidor');
nota('até expirar sozinho — quem tiver uma cópia continua dentro.');

secao('Pela API, ponta a ponta');

$router = Kernel::router();
$login = $router->resolver(Request::falsa('POST', '/api/v1/auth/login', [
    'email' => 'admin@exemplo.com',
    'senha' => 'senha-de-estudo',
]));
$dados = json_decode($login->body, true)['data'] ?? [];

checa('POST /auth/login devolve o par', $login->status === 200 && isset($dados['access']), "HTTP {$login->status}");

$errado = $router->resolver(Request::falsa('POST', '/api/v1/auth/login', [
    'email' => 'admin@exemplo.com',
    'senha' => 'errada',
]));
checa('senha errada dá 401', $errado->status === 401, json_decode($errado->body, true)['error']['message'] ?? '');

$eu = $router->resolver(Request::comToken('GET', '/api/v1/auth/eu', $dados['access']));
checa('GET /auth/eu com Bearer funciona', $eu->status === 200, '');

$semToken = $router->resolver(Request::falsa('GET', '/api/v1/auth/eu'));
checa('sem Bearer dá 401', $semToken->status === 401, 'Token ausente.');

secao('Por que token OPACO e não JWT auto-contido');

nota('JWT sem estado no servidor não pode ser revogado antes de expirar.');
nota('Para revogar, você precisaria de uma blocklist — e aí já guarda estado,');
nota('perdendo a vantagem. Token opaco: string aleatória, o banco decide se vale.');

fecharAula();
