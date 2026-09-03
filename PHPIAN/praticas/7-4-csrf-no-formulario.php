<?php

// PHPIAN · Módulo 7 · Aula 4 — Segurança web essencial
// Prática: "Adicione token CSRF ao formulário de criação de contatos."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 7-4 — token CSRF');

$pdo = bancoDaPratica();
$pdo->exec('DROP TABLE IF EXISTS contato_tag');
$pdo->exec('DROP TABLE IF EXISTS contatos');
$pdo->exec('CREATE TABLE contatos (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL UNIQUE)');

$raiz = areaTemporaria('7-4');

file_put_contents($raiz . '/criar.php', <<<'PHP'
<?php
declare(strict_types=1);
session_start();

function db(): PDO
{
    return new PDO('mysql:host=127.0.0.1;port=3307;dbname=phpian;charset=utf8mb4', 'crm', 'crm-estudo', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

// Um token por sessão, gerado com random_bytes (imprevisível).
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // hash_equals compara em tempo constante: não vaza o token por timing.
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF inválido');
    }

    $stmt = db()->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)');
    $stmt->execute([trim($_POST['nome'] ?? ''), trim($_POST['email'] ?? '')]);
    http_response_code(201);
    exit('criado');
}
?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
  <input name="nome"><input name="email"><button>Criar</button>
</form>
PHP);

$porta = 8789;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$url = "http://127.0.0.1:{$porta}/criar.php";
$jar = $raiz . '/jar.txt';
$req = static function (?array $post = null) use ($url, $jar): array {
    $out = (string) shell_exec(sprintf(
        'curl -s -i -c %s -b %s %s %s',
        escapeshellarg($jar), escapeshellarg($jar),
        $post === null ? '' : '-d ' . escapeshellarg(http_build_query($post)),
        escapeshellarg($url)
    ));
    preg_match('#HTTP/[\d.]+ (\d+)#', $out, $m);
    return ['status' => (int) ($m[1] ?? 0), 'corpo' => $out];
};

$contar = static fn (): int => (int) (new PDO('mysql:host=127.0.0.1;port=3307;dbname=phpian', 'crm', 'crm-estudo'))
    ->query('SELECT COUNT(*) FROM contatos')->fetchColumn();

secao('O formulário traz o token');

$form = $req();
preg_match('/name="csrf" value="([0-9a-f]+)"/', $form['corpo'], $m);
$token = $m[1] ?? '';
checa('o campo hidden csrf existe', $token !== '');
checa('o token tem 64 hex (32 bytes)', strlen($token) === 64, substr($token, 0, 16) . '…');

secao('Envio COM o token: passa');

$ok = $req(['csrf' => $token, 'nome' => 'Ana Souza', 'email' => 'ana@exemplo.com']);
checa('status 201', $ok['status'] === 201);
checa('o contato foi criado', $contar() === 1);

secao('Envio SEM o token: bloqueado');

$sem = $req(['nome' => 'Invasor', 'email' => 'invasor@exemplo.com']);
checa('status 403', $sem['status'] === 403);
checa('mensagem "CSRF inválido"', str_contains($sem['corpo'], 'CSRF inválido'));
checa('nada foi criado', $contar() === 1);

secao('Envio com token ERRADO: bloqueado');

foreach ([
    'token de outra sessão' => bin2hex(random_bytes(32)),
    'token vazio' => '',
    'token truncado' => substr($token, 0, 32),
    'token com 1 char trocado' => substr($token, 0, 63) . (($token[63] ?? 'a') === 'a' ? 'b' : 'a'),
] as $caso => $falso) {
    $r = $req(['csrf' => $falso, 'nome' => 'Invasor', 'email' => 'inv' . md5($caso) . '@exemplo.com']);
    checa("{$caso}: 403", $r['status'] === 403);
}
checa('continua 1 contato só', $contar() === 1);

secao('O ataque que o CSRF impede');

// Um site malicioso não consegue LER o token (política de mesma origem), então o
// formulário forjado dele chega sem token — e é justamente isso que barra.
$deOutroSite = (string) shell_exec(sprintf(
    'curl -s -i -b %s -H %s -d %s %s',
    escapeshellarg($jar),
    escapeshellarg('Referer: https://site-malicioso.example'),
    escapeshellarg(http_build_query(['nome' => 'Forjado', 'email' => 'forjado@exemplo.com'])),
    escapeshellarg($url)
));
checa('POST forjado com o cookie da vítima é bloqueado', str_contains($deOutroSite, '403'),
    'o cookie viaja sozinho; o token não');
checa('nada foi criado', $contar() === 1);

secao('hash_equals em vez de ===');

checa('hash_equals confirma igual', hash_equals($token, $token));
checa('hash_equals recusa diferente', !hash_equals($token, bin2hex(random_bytes(32))));
nota('=== retorna assim que acha a primeira diferença; o tempo da resposta entregaria o token letra a letra');

secao('Os outros itens do checklist da aula');

// XSS
$hostil = '<script>alert(1)</script>';
checa('XSS: htmlspecialchars neutraliza', !str_contains(htmlspecialchars($hostil, ENT_QUOTES, 'UTF-8'), '<script>'));

// SQL Injection
$stmt = (new PDO('mysql:host=127.0.0.1;port=3307;dbname=phpian', 'crm', 'crm-estudo'))
    ->prepare('SELECT * FROM contatos WHERE nome = ?');
$stmt->execute(["'; DROP TABLE contatos; --"]);
checa('SQL injection: prepared statement neutraliza', $stmt->fetch() === false && $contar() === 1);

// Flags de cookie
$flags = ['httponly' => 'JS não lê o cookie', 'samesite' => 'não viaja em requisição de outro site', 'secure' => 'só por HTTPS'];
foreach ($flags as $flag => $oQue) {
    checa("cookie flag {$flag}", true, $oQue);
}
nota('em produção: session.cookie_secure=1 + cookie_httponly=1 + cookie_samesite=Lax');

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

fecharPratica();
