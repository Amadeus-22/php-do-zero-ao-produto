<?php

// PHPIAN · Módulo 4 · Aula 2 — GET, POST e formulários
// Prática: "Monte um formulário de contato (nome, e-mail, mensagem) e uma página
// que exibe os dados sanitizados."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 4-2 — formulário de contato');

secao('As duas páginas');

$raiz = areaTemporaria('4-2');

file_put_contents($raiz . '/contato.php', <<<'PHP'
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Contato</title></head>
<body>
  <form method="post" action="enviar.php">
    <label>Nome <input name="nome" required></label>
    <label>E-mail <input type="email" name="email" required></label>
    <label>Mensagem <textarea name="mensagem" required></textarea></label>
    <button type="submit">Enviar</button>
  </form>
</body>
</html>
PHP);

file_put_contents($raiz . '/enviar.php', <<<'PHP'
<?php

declare(strict_types=1);

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

$erros = [];
if ($nome === '') { $erros[] = 'Nome obrigatório'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = 'E-mail inválido'; }
if ($mensagem === '') { $erros[] = 'Mensagem obrigatória'; }

if ($erros !== []) {
    http_response_code(422);
    echo '<ul class="erros">';
    foreach ($erros as $e) {
        echo '<li>', htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), '</li>';
    }
    echo '</ul>';
    exit;
}

// A regra de ouro da aula: nada sai sem htmlspecialchars.
$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

echo '<h1>Olá, ', $esc($nome), '</h1>';
echo '<p>E-mail: ', $esc($email), '</p>';
echo '<p>Mensagem: ', $esc($mensagem), '</p>';
PHP);

$porta = 8796;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$postar = static function (array $dados) use ($porta): array {
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($dados),
        'ignore_errors' => true,
    ]]);
    $corpo = (string) @file_get_contents("http://127.0.0.1:{$porta}/enviar.php", false, $ctx);
    return ['corpo' => $corpo, 'status' => $http_response_header[0] ?? ''];
};

secao('O formulário');

$form = (string) @file_get_contents("http://127.0.0.1:{$porta}/contato.php");
checa('form é method="post"', str_contains($form, 'method="post"'));
foreach (['nome', 'email', 'mensagem'] as $campo) {
    checa("tem o campo {$campo}", str_contains($form, "name=\"{$campo}\""));
}

secao('Envio válido');

$ok = $postar(['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com', 'mensagem' => 'Olá, tudo bem?']);
checa('status 200', str_contains($ok['status'], '200'), $ok['status']);
checa('o nome voltou na página', str_contains($ok['corpo'], 'Olá, Ana Souza'));
checa('o e-mail voltou', str_contains($ok['corpo'], 'ana@exemplo.com'));

secao('Envio inválido');

$ruim = $postar(['nome' => '', 'email' => 'sem-arroba', 'mensagem' => '']);
checa('status 422', str_contains($ruim['status'], '422'), 'a aula usa 422 para validação');
checa('lista "Nome obrigatório"', str_contains($ruim['corpo'], 'Nome obrigatório'));
checa('lista "E-mail inválido"', str_contains($ruim['corpo'], 'E-mail inválido'));
checa('lista "Mensagem obrigatória"', str_contains($ruim['corpo'], 'Mensagem obrigatória'));

secao('A regra de ouro: htmlspecialchars contra XSS');

$ataque = $postar([
    'nome' => '<script>alert("xss")</script>',
    'email' => 'x@exemplo.com',
    'mensagem' => '<img src=x onerror=alert(1)>',
]);
checa('o <script> NÃO saiu como tag', !str_contains($ataque['corpo'], '<script>alert'));
checa('saiu escapado como &lt;script&gt;', str_contains($ataque['corpo'], '&lt;script&gt;'));
checa('o onerror do img também foi escapado', !str_contains($ataque['corpo'], '<img src=x'));
checa('as aspas foram escapadas (ENT_QUOTES)', str_contains($ataque['corpo'], '&quot;') || !str_contains($ataque['corpo'], '"xss"'),
    'sem ENT_QUOTES, aspas simples passariam e quebrariam atributo');

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

secao('Escapar é na SAÍDA, não na entrada');

// Se escapasse ao gravar, o banco guardaria &lt;b&gt; e o dado ficaria corrompido
// para sempre. A aula está certa em escapar só na hora de imprimir.
$guardado = 'Ana & Bruno <sócios>';
checa('o valor guardado permanece cru', $guardado === 'Ana & Bruno <sócios>');
checa('o valor impresso sai escapado', htmlspecialchars($guardado, ENT_QUOTES, 'UTF-8') === 'Ana &amp; Bruno &lt;sócios&gt;');

fecharPratica();
