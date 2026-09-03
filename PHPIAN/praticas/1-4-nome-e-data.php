<?php

// PHPIAN · Módulo 1 · Aula 4 — Hello World e sintaxe
// Prática: "Mostre seu nome e a data de hoje com date('d/m/Y')."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 1-4 — nome e data');

secao('O que a prática pede');

$nome = 'Barbosa';
$hoje = date('d/m/Y');

$saida = "{$nome} — {$hoje}";
nota($saida);

checa('o nome aparece', str_contains($saida, $nome));
checa('a data usa o formato d/m/Y', (bool) preg_match('#^\d{2}/\d{2}/\d{4}$#', $hoje), $hoje);
checa('é a data de hoje', $hoje === (new DateTimeImmutable())->format('d/m/Y'));

secao('As regras de sintaxe da aula, testadas');

// "Instruções terminam com ;" — sem o ponto e vírgula o PHP nem inicia.
$semPontoEVirgula = "<?php\n\$x = 1\necho \$x;\n";
$arq = areaTemporaria('1-4') . '/quebrado.php';
file_put_contents($arq, $semPontoEVirgula);
$lint = (string) shell_exec('php -l ' . escapeshellarg($arq) . ' 2>&1');
checa('falta de ";" é erro de parse (o PHP nem inicia)', str_contains($lint, 'syntax error'),
    trim(explode("\n", $lint)[0]));

// "echo envia texto à saída"
ob_start();
echo 'saiu';
$capturado = ob_get_clean();
checa('echo escreve na saída', $capturado === 'saiu');

// "Em arquivos só PHP, omita o ?> final"
checa('este arquivo não fecha a tag PHP', !str_contains(file_get_contents(__FILE__), "\n?>"),
    'evita espaço em branco depois do fechamento');

secao('HTML + PHP na mesma página');

// O segundo bloco da aula: PHP dentro do HTML.
$html = sprintf(
    '<!DOCTYPE html><html lang="pt-BR"><body><h1>%s</h1><p>%s</p></body></html>',
    htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'),
    $hoje
);
checa('o nome entrou no HTML', str_contains($html, "<h1>{$nome}</h1>"));
checa('a data entrou no HTML', str_contains($html, "<p>{$hoje}</p>"));

// A aula ainda não ensinou escape (isso é 4-2), mas o hábito começa aqui.
$nomeHostil = '<script>alert(1)</script>';
$htmlSeguro = '<h1>' . htmlspecialchars($nomeHostil, ENT_QUOTES, 'UTF-8') . '</h1>';
checa('nome com script sai escapado', !str_contains($htmlSeguro, '<script>'), $htmlSeguro);

fecharPratica();
