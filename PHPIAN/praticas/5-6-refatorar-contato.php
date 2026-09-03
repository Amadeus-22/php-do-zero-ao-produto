<?php

// PHPIAN · Módulo 5 · Aula 6 — Boas práticas (PSR leve)
// Prática: "Revise o projeto de contato: extraia 2 funções + includes. contato.php
// principal com menos de 40 linhas de lógica."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 5-6 — refatorar o contato');

$raiz = areaTemporaria('5-6');
mkdir($raiz . '/src');
mkdir($raiz . '/templates');

secao('As 2 funções extraídas');

file_put_contents($raiz . '/src/contato.php', <<<'PHP'
<?php

declare(strict_types=1);

/**
 * Função 1 — decide se a mensagem é aceita.
 * @return list<string> lista de erros; vazia = válido
 */
function validarMensagem(array $dados): array
{
    $erros = [];

    if (mb_strlen(trim((string) ($dados['nome'] ?? ''))) < 3) {
        $erros[] = 'Nome inválido';
    }
    if (!filter_var(trim((string) ($dados['email'] ?? '')), FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail inválido';
    }
    if (trim((string) ($dados['mensagem'] ?? '')) === '') {
        $erros[] = 'Mensagem obrigatória';
    }

    return $erros;
}

/** Função 2 — grava a mensagem aceita. */
function gravarMensagem(array $dados, string $arquivo): bool
{
    $linha = sprintf(
        "%s | %s | %s | %s\n",
        date('c'),
        trim((string) $dados['nome']),
        trim((string) $dados['email']),
        trim((string) $dados['mensagem'])
    );

    return file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX) !== false;
}
PHP);

require $raiz . '/src/contato.php';

checa('validarMensagem existe', function_exists('validarMensagem'));
checa('gravarMensagem existe', function_exists('gravarMensagem'));
checa('são 2 funções, como pedido', count(array_filter(['validarMensagem', 'gravarMensagem'], 'function_exists')) === 2);

secao('As funções funcionam isoladas — é o ganho da extração');

checa('dados bons: 0 erros', validarMensagem(['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com', 'mensagem' => 'Oi']) === []);
checa('nome curto é pego', in_array('Nome inválido', validarMensagem(['nome' => 'Al', 'email' => 'a@b.co', 'mensagem' => 'x']), true));
checa('e-mail ruim é pego', in_array('E-mail inválido', validarMensagem(['nome' => 'Ana', 'email' => 'x', 'mensagem' => 'x']), true));
checa('vazio dá 3 erros', count(validarMensagem([])) === 3);

$arquivo = $raiz . '/mensagens.txt';
checa('gravarMensagem grava', gravarMensagem(['nome' => 'Ana', 'email' => 'ana@exemplo.com', 'mensagem' => 'Oi'], $arquivo));
checa('e acumula (FILE_APPEND)', gravarMensagem(['nome' => 'Bruno', 'email' => 'b@exemplo.com', 'mensagem' => 'Olá'], $arquivo)
    && count(array_filter(explode("\n", (string) file_get_contents($arquivo)))) === 2);
nota('testar sem subir servidor só é possível porque a lógica saiu da página');

secao('Os includes');

file_put_contents($raiz . '/templates/header.php', "<!DOCTYPE html>\n<html lang=\"pt-BR\">\n<head><meta charset=\"UTF-8\"><title>Contato</title></head>\n<body>\n");
file_put_contents($raiz . '/templates/footer.php', "</body>\n</html>\n");
file_put_contents($raiz . '/templates/formulario.php', <<<'PHP'
<form method="post" action="contato.php">
  <label>Nome <input name="nome" required></label>
  <label>E-mail <input type="email" name="email" required></label>
  <label>Mensagem <textarea name="mensagem" required></textarea></label>
  <button type="submit">Enviar</button>
</form>
PHP);

foreach (['header.php', 'footer.php', 'formulario.php'] as $t) {
    checa("templates/{$t} extraído", is_file($raiz . '/templates/' . $t));
}

secao('O contato.php principal');

file_put_contents($raiz . '/contato.php', <<<'PHP'
<?php

declare(strict_types=1);

require_once __DIR__ . '/src/contato.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $erros = validarMensagem($_POST);

    if ($erros === []) {
        gravarMensagem($_POST, __DIR__ . '/mensagens.txt');
        $_SESSION['flash_ok'] = 'Mensagem enviada!';
    } else {
        $_SESSION['flash_erro'] = implode(' · ', $erros);
    }

    header('Location: contato.php');
    exit;
}

$ok = $_SESSION['flash_ok'] ?? null;
$erro = $_SESSION['flash_erro'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_erro']);

$esc = static fn (?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

require __DIR__ . '/templates/header.php';
if ($ok !== null) { echo '<p class="ok">', $esc($ok), '</p>'; }
if ($erro !== null) { echo '<p class="erro">', $esc($erro), '</p>'; }
require __DIR__ . '/templates/formulario.php';
require __DIR__ . '/templates/footer.php';
PHP);

$codigo = (string) file_get_contents($raiz . '/contato.php');
$linhas = explode("\n", $codigo);

// "menos de 40 linhas de lógica": conta linha de código, fora vazias e comentários
$logica = array_filter($linhas, static fn (string $l): bool =>
    trim($l) !== '' && !str_starts_with(trim($l), '//') && trim($l) !== '<?php' && trim($l) !== 'declare(strict_types=1);');

checa('contato.php tem menos de 40 linhas de lógica', count($logica) < 40, count($logica) . ' linhas');
checa('a validação não está mais aqui', !str_contains($codigo, 'FILTER_VALIDATE_EMAIL'), 'foi para src/');
checa('a gravação não está mais aqui', !str_contains($codigo, 'FILE_APPEND'), 'foi para src/');
checa('o HTML não está mais aqui', !str_contains($codigo, '<!DOCTYPE'), 'foi para templates/');
checa('sintaxe válida', str_contains((string) shell_exec('php -l ' . escapeshellarg($raiz . '/contato.php') . ' 2>&1'), 'No syntax errors'));

secao('E continua funcionando');

$porta = 8792;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

unlink($arquivo);
$cookie = $raiz . '/c.txt';
$url = "http://127.0.0.1:{$porta}/contato.php";
shell_exec(sprintf('curl -s -o /dev/null -c %s -b %s -d %s %s', escapeshellarg($cookie), escapeshellarg($cookie),
    escapeshellarg(http_build_query(['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com', 'mensagem' => 'Depois de refatorar'])), escapeshellarg($url)));
$pagina = (string) shell_exec(sprintf('curl -s -c %s -b %s %s', escapeshellarg($cookie), escapeshellarg($cookie), escapeshellarg($url)));

checa('o flash de sucesso aparece', str_contains($pagina, 'Mensagem enviada!'));
checa('o layout dos includes veio junto', str_contains($pagina, '<!DOCTYPE html>') && str_contains($pagina, '<form'));
checa('a mensagem foi gravada', is_file($arquivo) && str_contains((string) file_get_contents($arquivo), 'Depois de refatorar'));

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

secao('Os itens da lista de boas práticas');

checa('indentação de 4 espaços (PSR-12)', !str_contains($codigo, "\t"));
checa('não silencia erro com @', !str_contains($codigo, '@file') && !str_contains($codigo, '@require'));
checa('separa HTML da lógica', str_contains($codigo, 'templates/'));
checa('nomes claros, sem abreviação obscura', str_contains($codigo, 'validarMensagem') && str_contains($codigo, 'gravarMensagem'));

fecharPratica();
