<?php

// PHPIAN · Módulo 2 · Aula 5 — Erros, debug e try/catch
// Prática: "Provoque de propósito um erro (esqueça um ;), leia a mensagem, corrija.
// Depois envolva uma divisão por zero em try/catch."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 2-5 — erro provocado e try/catch');

secao('Parte 1 — provocar o erro, ler a mensagem, corrigir');

$area = areaTemporaria('2-5');

// O erro é provocado em arquivo separado, de propósito: um parse error no arquivo
// que está rodando impediria o PHP de iniciar — e não haveria o que ler.
$quebrado = $area . '/quebrado.php';
file_put_contents($quebrado, "<?php\n\$total = 10\necho \$total;\n");

$mensagem = trim((string) shell_exec('php -l ' . escapeshellarg($quebrado) . ' 2>&1'));
nota($mensagem);

checa('o PHP recusou o arquivo', str_contains($mensagem, 'syntax error'));
checa('a mensagem diz o ARQUIVO', str_contains($mensagem, 'quebrado.php'));
checa('a mensagem diz a LINHA', (bool) preg_match('/line (\d+)/', $mensagem, $m), 'linha ' . ($m[1] ?? '?'));
checa('aponta a linha 3, onde o PHP percebeu', ($m[1] ?? '') === '3',
    'o ";" falta na 2 — o erro é notado só na próxima instrução');

// Corrigindo
$corrigido = $area . '/corrigido.php';
file_put_contents($corrigido, "<?php\n\$total = 10;\necho \$total;\n");
checa('com o ";" o arquivo compila', str_contains((string) shell_exec('php -l ' . escapeshellarg($corrigido) . ' 2>&1'), 'No syntax errors'));
checa('e roda', trim((string) shell_exec('php ' . escapeshellarg($corrigido))) === '10');

secao('Parte 2 — divisão por zero em try/catch');

function dividir(float $a, float $b): float
{
    if ($b === 0.0) {
        throw new InvalidArgumentException('Divisão por zero');
    }
    return $a / $b;
}

$resultado = null;
$erro = null;
try {
    $resultado = dividir(10, 0);
} catch (InvalidArgumentException $e) {
    $erro = 'Erro: ' . $e->getMessage();
}

nota((string) $erro);
checa('não retornou valor', $resultado === null);
checa('capturou a exceção', $erro === 'Erro: Divisão por zero');
checa('o script continuou vivo depois do catch', true, 'é para isso que serve o try/catch');
checa('o caminho feliz segue funcionando', dividir(10, 2) === 5.0);

secao('O que o PHP faz SEM o guarda da função');

// 10/0 no PHP 8 é DivisionByZeroError (Error, não Exception) — e catch(Exception)
// NÃO pega. É a pegadinha que a aula não menciona.
checaExcecao('10 / 0 lança DivisionByZeroError', \DivisionByZeroError::class, static fn () => 10 / 0);

$pegouComException = false;
try {
    try {
        $x = 10 / 0;
    } catch (Exception $e) {
        $pegouComException = true;
    }
} catch (Throwable $e) {
    // caiu aqui porque o catch(Exception) acima não serviu
}
checa('catch (Exception) NÃO pega DivisionByZeroError', $pegouComException === false,
    'precisa de catch (DivisionByZeroError) ou catch (Throwable)');

checa('10 % 0 também lança', (static function (): bool {
    try { $x = 10 % 0; return false; } catch (DivisionByZeroError) { return true; }
})(), 'Modulo by zero');

secao('Ferramentas de debug da aula');

ob_start();
var_dump('10');
$dump = ob_get_clean();
checa('var_dump mostra o TIPO, não só o valor', str_contains($dump, 'string(2) "10"'), trim($dump));

ob_start();
print_r(['a' => 1, 'b' => [2, 3]]);
$print = ob_get_clean();
checa('print_r abre o array aninhado', str_contains($print, '[b] => Array'));

secao('Os erros comuns que a aula lista');

$exemplos = [
    'Undefined variable' => "<?php\necho \$naoDefinida;\n",
    'Call to undefined function' => "<?php\nfuncaoQueNaoExiste();\n",
    'Failed to open stream' => "<?php\nrequire '/caminho/que/nao/existe.php';\n",
];
foreach ($exemplos as $esperado => $codigo) {
    $f = $area . '/e' . md5($esperado) . '.php';
    file_put_contents($f, $codigo);
    $saida = (string) shell_exec('php -d display_errors=1 ' . escapeshellarg($f) . ' 2>&1');
    checa("\"{$esperado}\" acontece como descrito", str_contains($saida, $esperado));
}

fecharPratica();
