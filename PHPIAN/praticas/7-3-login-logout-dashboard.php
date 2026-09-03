<?php

// PHPIAN · Módulo 7 · Aula 3 — Autenticação simples
// Prática: "Implemente login + logout + página dashboard.php só para autenticados."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 7-3 — login, logout e área protegida');

$pdo = bancoDaPratica();
$pdo->exec('DROP TABLE IF EXISTS users');
$pdo->exec(<<<'SQL'
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL
)
SQL);
$pdo->prepare('INSERT INTO users (nome, email, senha_hash) VALUES (?, ?, ?)')
    ->execute(['Ana Souza', 'ana@exemplo.com', password_hash('senha-de-estudo', PASSWORD_DEFAULT)]);

$raiz = areaTemporaria('7-3');

$comum = <<<'PHP'
<?php
declare(strict_types=1);

function db(): PDO
{
    return new PDO('mysql:host=127.0.0.1;port=3307;dbname=phpian;charset=utf8mb4', 'crm', 'crm-estudo', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

/** O gate: nenhuma página protegida roda uma linha sem passar por aqui. */
function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}
PHP;
file_put_contents($raiz . '/comum.php', $comum);

file_put_contents($raiz . '/login.php', <<<'PHP'
<?php
require __DIR__ . '/comum.php';
session_start();

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    $stmt = db()->prepare('SELECT id, nome, senha_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha_hash'])) {
        // Antes de gravar qualquer coisa: id novo, para matar sessão fixada.
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        header('Location: /dashboard.php');
        exit;
    }

    // Mensagem única: não revela se o e-mail existe.
    $erro = 'E-mail ou senha inválidos';
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Entrar</title></head><body>
<?php if ($erro !== null): ?><p class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<form method="post"><input name="email"><input type="password" name="senha"><button>Entrar</button></form>
</body></html>
PHP);

file_put_contents($raiz . '/dashboard.php', <<<'PHP'
<?php
require __DIR__ . '/comum.php';
session_start();
requireAuth();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Painel</title></head><body>
<h1>Olá, <?= htmlspecialchars($_SESSION['user_nome'], ENT_QUOTES, 'UTF-8') ?></h1>
<a href="/logout.php">Sair</a>
</body></html>
PHP);

file_put_contents($raiz . '/logout.php', <<<'PHP'
<?php
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: /login.php');
exit;
PHP);

$porta = 8790;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$base = "http://127.0.0.1:{$porta}";
$req = static function (string $caminho, string $jar, ?array $post = null) use ($base): array {
    $out = (string) shell_exec(sprintf(
        'curl -s -i -c %s -b %s %s %s',
        escapeshellarg($jar), escapeshellarg($jar),
        $post === null ? '' : '-d ' . escapeshellarg(http_build_query($post)),
        escapeshellarg($base . $caminho)
    ));
    preg_match('#HTTP/[\d.]+ (\d+)#', $out, $m);
    preg_match('#^Location: (.+)$#mi', $out, $l);
    return ['status' => (int) ($m[1] ?? 0), 'local' => trim($l[1] ?? ''), 'corpo' => $out];
};

$jar = $raiz . '/jar.txt';

secao('Deslogado: o dashboard é inalcançável');

$r = $req('/dashboard.php', $jar);
checa('dashboard responde 302', $r['status'] === 302);
checa('redireciona para /login.php', $r['local'] === '/login.php');
checa('e NÃO vaza o conteúdo', !str_contains($r['corpo'], '<h1>Olá'), 'o exit cortou antes de imprimir');

secao('Login com senha errada');

$r = $req('/login.php', $jar, ['email' => 'ana@exemplo.com', 'senha' => 'errada']);
checa('responde 200, não redireciona', $r['status'] === 200);
checa('mostra o erro', str_contains($r['corpo'], 'E-mail ou senha inválidos'));

$inexistente = $req('/login.php', $jar, ['email' => 'ninguem@exemplo.com', 'senha' => 'qualquer']);
checa('e-mail inexistente dá a MESMA mensagem', str_contains($inexistente['corpo'], 'E-mail ou senha inválidos'),
    'não revela quais e-mails estão cadastrados');

$aindaFora = $req('/dashboard.php', $jar);
checa('continua sem acesso ao dashboard', $aindaFora['status'] === 302);

secao('Login correto');

// Guarda o id de sessão de antes, para provar a rotação
$req('/login.php', $jar);
$antes = preg_match('/PHPSESSID\s+(\S+)/', (string) file_get_contents($jar), $m) ? $m[1] : '';

$r = $req('/login.php', $jar, ['email' => 'ana@exemplo.com', 'senha' => 'senha-de-estudo']);
checa('responde 302', $r['status'] === 302);
checa('redireciona para /dashboard.php', $r['local'] === '/dashboard.php');

$depois = preg_match('/PHPSESSID\s+(\S+)/', (string) file_get_contents($jar), $m2) ? $m2[1] : '';
checa('session_regenerate_id trocou o id', $antes !== '' && $depois !== '' && $antes !== $depois,
    'bloqueia fixação de sessão');

secao('Logado: o dashboard abre');

$d = $req('/dashboard.php', $jar);
checa('responde 200', $d['status'] === 200);
checa('mostra o nome do usuário', str_contains($d['corpo'], 'Olá, Ana Souza'));
checa('tem o link de sair', str_contains($d['corpo'], '/logout.php'));

secao('Logout');

$l = $req('/logout.php', $jar);
checa('responde 302 para o login', $l['status'] === 302 && $l['local'] === '/login.php');

$depoisDoLogout = $req('/dashboard.php', $jar);
checa('o dashboard volta a barrar', $depoisDoLogout['status'] === 302);
checa('e manda para o login', $depoisDoLogout['local'] === '/login.php');

secao('SQL injection no formulário de login');

foreach (["' OR '1'='1", "admin'--", "' OR 1=1 -- "] as $ataque) {
    $jarAtaque = $raiz . '/atk' . md5($ataque) . '.txt';
    $r = $req('/login.php', $jarAtaque, ['email' => $ataque, 'senha' => $ataque]);
    checa(sprintf('%-16s não entra', mb_substr($ataque, 0, 16)), $r['status'] === 200 && str_contains($r['corpo'], 'inválidos'));
    checa('  e o dashboard segue barrado', $req('/dashboard.php', $jarAtaque)['status'] === 302);
}

secao('Force browsing');

// A aula avisa: "autorize no servidor, não só esconda links". O dashboard nunca
// foi linkado para quem está deslogado — e mesmo assim digitar a URL não entra.
$jarNovo = $raiz . '/novo.txt';
checa('digitar a URL direto não basta', $req('/dashboard.php', $jarNovo)['status'] === 302,
    'a autorização está no servidor, no requireAuth()');

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

fecharPratica();
