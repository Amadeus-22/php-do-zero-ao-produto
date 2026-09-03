<?php

// PHPIAN · Módulo 4 · Aula 5 — Projeto relâmpago: contato + redirect
// Prática: "Monte contato.php completo (form + processamento no mesmo arquivo).
// Envie 2 mensagens e abra mensagens.txt para conferir."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 4-5 — projeto relâmpago (PRG)');

secao('O contato.php completo');

$raiz = areaTemporaria('4-5');

file_put_contents($raiz . '/contato.php', <<<'PHP'
<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $msg = trim($_POST['mensagem'] ?? '');

    $erros = [];
    if ($nome === '' || mb_strlen($nome) < 3) { $erros[] = 'Nome inválido'; }
    if (!$email) { $erros[] = 'E-mail inválido'; }
    if ($msg === '') { $erros[] = 'Mensagem obrigatória'; }

    if ($erros !== []) {
        $_SESSION['flash_erro'] = implode(' · ', $erros);
        header('Location: contato.php');
        exit;                                  // sem o exit, o resto do arquivo ainda roda
    }

    $linha = date('c') . " | {$nome} | {$email} | {$msg}\n";
    file_put_contents(__DIR__ . '/mensagens.txt', $linha, FILE_APPEND | LOCK_EX);

    $_SESSION['flash_ok'] = 'Mensagem enviada!';
    header('Location: contato.php');
    exit;
}

// GET: lê e CONSOME o flash — ele só pode aparecer uma vez.
$ok = $_SESSION['flash_ok'] ?? null;
$erro = $_SESSION['flash_erro'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_erro']);

$esc = static fn (?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Contato</title></head>
<body>
  <?php if ($ok !== null): ?><p class="ok"><?= $esc($ok) ?></p><?php endif; ?>
  <?php if ($erro !== null): ?><p class="erro"><?= $esc($erro) ?></p><?php endif; ?>
  <form method="post" action="contato.php">
    <label>Nome <input name="nome" required></label>
    <label>E-mail <input type="email" name="email" required></label>
    <label>Mensagem <textarea name="mensagem" required></textarea></label>
    <button type="submit">Enviar</button>
  </form>
</body>
</html>
PHP);

$porta = 8795;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$cookie = $raiz . '/cookies.txt';
$curl = static function (string $url, ?array $post = null, bool $seguir = false) use ($cookie): array {
    $cmd = sprintf(
        'curl -s -i %s -c %s -b %s %s %s',
        $seguir ? '-L' : '',
        escapeshellarg($cookie),
        escapeshellarg($cookie),
        $post === null ? '' : '-d ' . escapeshellarg(http_build_query($post)),
        escapeshellarg($url)
    );
    $out = (string) shell_exec($cmd);
    return ['bruto' => $out, 'status' => (int) (preg_match('#HTTP/[\d.]+ (\d+)#', $out, $m) ? $m[1] : 0)];
};

$url = "http://127.0.0.1:{$porta}/contato.php";

secao('Mensagem 1 — o ciclo POST → 302 → GET');

$r = $curl($url, ['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com', 'mensagem' => 'Primeira mensagem']);
checa('o POST respondeu 302, não 200', $r['status'] === 302, 'é o R do PRG');
checa('com header Location: contato.php', stripos($r['bruto'], 'Location: contato.php') !== false);
checa('e sem corpo HTML', !str_contains($r['bruto'], '<form'), 'o exit cortou antes de imprimir');

$g = $curl($url);
checa('o GET seguinte mostra o flash de sucesso', str_contains($g['bruto'], 'Mensagem enviada!'));

$g2 = $curl($url);
checa('recarregar NÃO mostra o flash de novo', !str_contains($g2['bruto'], 'Mensagem enviada!'),
    'o unset consumiu — é o que faz "flash" ser flash');

secao('Mensagem 2');

$curl($url, ['nome' => 'Bruno Lima', 'email' => 'bruno@exemplo.com', 'mensagem' => 'Segunda mensagem']);
$curl($url);

secao('Abrindo mensagens.txt');

$arquivo = $raiz . '/mensagens.txt';
checa('o arquivo existe', is_file($arquivo));
$linhas = array_values(array_filter(explode("\n", (string) file_get_contents($arquivo))));
foreach ($linhas as $l) {
    nota($l);
}
checa('tem exatamente 2 linhas', count($linhas) === 2);
checa('a 1ª é da Ana', str_contains($linhas[0] ?? '', 'Ana Souza') && str_contains($linhas[0] ?? '', 'Primeira mensagem'));
checa('a 2ª é do Bruno', str_contains($linhas[1] ?? '', 'Bruno Lima'));
checa('cada linha começa com data ISO', (bool) preg_match('/^\d{4}-\d{2}-\d{2}T/', $linhas[0] ?? ''), 'date("c")');
checa('FILE_APPEND acumulou em vez de sobrescrever', count($linhas) === 2);

secao('Envio inválido');

$ruim = $curl($url, ['nome' => 'Al', 'email' => 'sem-arroba', 'mensagem' => '']);
checa('também redireciona (302)', $ruim['status'] === 302);
$depois = $curl($url);
checa('o flash de erro lista os 3 problemas',
    str_contains($depois['bruto'], 'Nome inválido')
    && str_contains($depois['bruto'], 'E-mail inválido')
    && str_contains($depois['bruto'], 'Mensagem obrigatória'));
checa('e nada foi gravado', count(array_filter(explode("\n", (string) file_get_contents($arquivo)))) === 2);

secao('O callout: "sempre exit depois de header Location"');

// Sem o exit o PHP continua executando: grava de novo, imprime HTML depois do
// redirect, e o efeito colateral acontece mesmo o navegador não mostrando.
file_put_contents($raiz . '/sem-exit.php', <<<'PHP'
<?php
header('Location: /destino.php');
file_put_contents(__DIR__ . '/efeito.txt', "rodei mesmo assim\n", FILE_APPEND);
echo 'este texto também foi gerado';
PHP);
$curl("http://127.0.0.1:{$porta}/sem-exit.php");
checa('sem exit, o código DEPOIS do redirect executou', is_file($raiz . '/efeito.txt'),
    'gravou o arquivo mesmo tendo mandado 302');

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

secao('Por que PRG: o F5 do navegador');

nota('sem PRG, a resposta do POST fica no histórico e o F5 reenvia o formulário');
nota('com PRG, o F5 recarrega um GET inofensivo — foi o que o teste acima mostrou');

fecharPratica();
