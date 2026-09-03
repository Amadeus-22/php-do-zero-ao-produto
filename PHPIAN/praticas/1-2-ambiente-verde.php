<?php

// PHPIAN · Módulo 1 · Aula 2 — Passo a passo: do zero ao localhost
// Prática: "Tire um print da saída de php -v e da URL do localhost funcionando."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 1-2 — ambiente verde');

secao('php -v');

// O print é para o aluno; o que importa é o que ele provaria. Verificamos direto.
checa('PHP 8.2 ou superior', version_compare(PHP_VERSION, '8.2.0', '>='), 'PHP ' . PHP_VERSION);
checa('rodando pela linha de comando', PHP_SAPI === 'cli', 'SAPI: ' . PHP_SAPI);

secao('Extensões que o curso vai usar');

foreach (['pdo_mysql' => 'banco (Módulo 6)', 'mbstring' => 'acentos', 'json' => 'APIs (3-5)', 'fileinfo' => 'upload (4-4)'] as $ext => $pra) {
    checa("extensão {$ext}", extension_loaded($ext), $pra);
}

secao('O localhost respondendo');

// A aula manda abrir http://localhost/phpian-aula/info.php no Apache do Laragon.
// Aqui subimos o servidor embutido do próprio PHP, que prova a mesma coisa: um
// arquivo .php servido por HTTP devolve HTML processado, não o código-fonte.
$raiz = areaTemporaria('1-2');
file_put_contents($raiz . '/info.php', "<?php\nphpinfo();\n");
file_put_contents($raiz . '/ola.php', "<?php\necho '<h1>ambiente verde</h1>';\n");

$porta = 8799;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($raiz)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(700000);

$corpo = @file_get_contents("http://127.0.0.1:{$porta}/ola.php");
checa('o servidor respondeu', $corpo !== false);
checa('devolveu HTML processado', is_string($corpo) && str_contains($corpo, '<h1>ambiente verde</h1>'));
checa('NÃO devolveu o código-fonte PHP', is_string($corpo) && !str_contains($corpo, '<?php'),
    'é isso que "server-side" significa');

$info = @file_get_contents("http://127.0.0.1:{$porta}/info.php");
checa('info.php mostra a configuração do PHP', is_string($info) && str_contains($info, 'PHP Version'));

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

secao('O aviso da aula');

// "Apague info.php assim que confirmar. Em produção ele expõe demais."
unlink($raiz . '/info.php');
checa('info.php apagado depois do teste', !file_exists($raiz . '/info.php'),
    'em produção ele vaza caminhos, versões e extensões');

fecharPratica();
