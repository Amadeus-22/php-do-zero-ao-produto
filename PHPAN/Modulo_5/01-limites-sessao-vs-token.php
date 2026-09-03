<?php

// PHPAN · Módulo 5 · Aula 01 — Sessão vs token: limites de cada um
// metadados em aulas.json · a ideia em 01-limites-sessao-vs-token.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Auth\Sessao;
use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;
use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;

$pdo = bancoDaAula();

titulo('Aula 1 — Sessão vs token: limites de cada um');

secao('Uma rota, um mecanismo');

$comparacao = [
    'Painel web' => 'sessão (cookie HttpOnly) — o navegador manda sozinho',
    'API'        => 'token Bearer — funciona para app, script e outro backend',
];
foreach ($comparacao as $onde => $como) {
    printf("  %-12s %s\n", $onde, $como);
}

// A API NÃO aceita cookie de sessão: são mecanismos separados de propósito.
$_SESSION['usuario_id'] = 1;
$_SESSION['papel'] = 'admin';
$semBearer = Kernel::router()->resolver(Request::falsa('GET', '/api/v1/clientes'));
checa('sessão do painel NÃO autentica a API', $semBearer->status === 401, 'HTTP 401 — a API quer Bearer');
$_SESSION = [];

secao('Sessão: o cookie endurecido');

Sessao::iniciar();
$flags = [
    'session.cookie_httponly' => '1',   // JS não lê o cookie -> XSS não rouba a sessão
    'session.cookie_samesite' => 'Lax', // mitiga CSRF em navegação cross-site
    'session.use_strict_mode' => '1',   // ignora ID de sessão inventado pelo cliente
];
foreach ($flags as $flag => $esperado) {
    checa("{$flag} = {$esperado}", (string) ini_get($flag) === $esperado, '');
}

secao('SESSION FIXATION: por que regenerar o ID no login');

$usuario = Container::repositorioDeUsuarios()->salvar(
    Usuario::novo('Ana Admin', 'admin@exemplo.com', 'senha-de-estudo', Papel::ADMIN),
);

$idAntesDoLogin = session_id();       // o que um atacante teria "fixado"
Sessao::entrar($usuario);
$idDepoisDoLogin = session_id();

checa('o ID de sessão MUDA ao autenticar', $idAntesDoLogin !== $idDepoisDoLogin, 'session_regenerate_id(true)');
nota('Sem isso, um ID fixado pelo atacante antes do login fica autenticado junto');
nota('com a vítima — ele passa a estar dentro da conta sem saber a senha.');

secao('Logout: destruir no servidor E expirar o cookie');

checa('sessão ativa antes do logout', Sessao::usuarioId() !== null, 'usuario_id na sessão');
Sessao::sair();
checa('$_SESSION esvaziado', $_SESSION === [], '');
checa('sessão inativa', session_status() !== PHP_SESSION_ACTIVE, 'session_destroy()');
nota('Só session_destroy() não basta: o navegador continuaria mandando o cookie.');

secao('Sessão eterna é sessão roubada para sempre');

checa('existe teto de tempo', Sessao::TEMPO_MAXIMO === 7200, Sessao::TEMPO_MAXIMO . 's = 2h');

Sessao::iniciar();
$_SESSION['usuario_id'] = (int) $usuario->id();
$_SESSION['papel'] = 'admin';
$_SESSION['criado_em'] = time() - (Sessao::TEMPO_MAXIMO + 60); // envelhece à força

checa('sessão vencida é derrubada na próxima leitura', Sessao::usuarioId() === null, 'expirou -> sair()');

secao('Token: o outro lado');

$novo = Container::repositorioDeUsuarios()->salvar(
    Usuario::novo('Bruno', 'bruno@exemplo.com', 'senha-de-estudo', Papel::VENDEDOR),
);
$par = Container::tokenService()->emitirPar((int) $novo->id());

checa('access e refresh são strings distintas', $par['access'] !== $par['refresh'], '');
checa('64 chars hex = 32 bytes de entropia', strlen($par['access']) === 64, '');

$comBearer = Kernel::router()->resolver(Request::comToken('GET', '/api/v1/clientes', $par['access']));
checa('Bearer autentica a API', $comBearer->status === 200, 'HTTP 200');

secao('Riscos: cada mecanismo tem o seu');

$riscos = [
    'Sessão' => 'CSRF e session fixation. Logout é trivial (destrói no servidor).',
    'Token'  => 'XSS se ficar acessível a JS, e vazamento em log/URL.',
];
foreach ($riscos as $mecanismo => $risco) {
    printf("  %-8s %s\n", $mecanismo, $risco);
}
nota('localStorage NÃO é mais seguro que cookie: qualquer script na página o lê.');
nota('HTTPS protege o transporte — não protege contra XSS, CSRF ou token mal guardado.');

fecharAula();
