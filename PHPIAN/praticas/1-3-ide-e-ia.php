<?php

// PHPIAN · Módulo 1 · Aula 3 — IDE, Cursor e IA como copiloto
// Prática: "Peça à IA: crie um ola.php em PHP 8.3 que mostre Hello e a data atual,
// com comentários curtos. Depois leia linha a linha e apague o que não entender."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 1-3 — IDE e IA como copiloto');

secao('O ola.php que a prática manda pedir');

$raiz = areaTemporaria('1-3');
$arquivo = $raiz . '/ola.php';

// É este o código que a prática pede. Escrever é metade; a outra metade — a que a
// aula insiste — é conseguir explicar cada linha. Os comentários abaixo são essa
// explicação, e as verificações provam que o arquivo faz o que promete.
file_put_contents($arquivo, <<<'PHP'
<?php

declare(strict_types=1); // recusa conversão silenciosa de tipo

$nome = 'PHPIAN';                                  // quem estamos saudando
$agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));

echo "Hello, {$nome}!\n";                          // interpolação dentro de aspas duplas
echo $agora->format('d/m/Y H:i'), "\n";            // dia/mês/ano, como se lê aqui

PHP);

checa('o arquivo foi criado', is_file($arquivo));
checa('sintaxe válida', shell_exec('php -l ' . escapeshellarg($arquivo) . ' 2>&1') !== null
    && str_contains((string) shell_exec('php -l ' . escapeshellarg($arquivo) . ' 2>&1'), 'No syntax errors'));

$saida = (string) shell_exec('php ' . escapeshellarg($arquivo));
checa('mostra o Hello', str_contains($saida, 'Hello, PHPIAN!'));
checa('mostra a data de hoje', str_contains($saida, (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('d/m/Y')),
    trim(explode("\n", $saida)[1] ?? ''));

secao('"Peça versão PHP 8.3 explicitamente"');

$codigo = (string) file_get_contents($arquivo);
checa('usa strict_types (PHP moderno, não PHP 5)', str_contains($codigo, 'declare(strict_types=1)'));
checa('usa DateTimeImmutable, não date() solto', str_contains($codigo, 'DateTimeImmutable'));
checa('fixa o fuso, não confia no do servidor', str_contains($codigo, 'America/Sao_Paulo'));
checa('tem comentários curtos, como pedido', substr_count($codigo, '//') >= 4);

secao('"Se não consegue explicar em voz alta, só copiou"');

// A regra de ouro da aula, virada em teste: para cada linha com comentário, o
// comentário precisa dizer algo além de repetir o código.
$linhas = array_filter(explode("\n", $codigo), static fn ($l) => str_contains($l, '//'));
$explicadas = 0;
foreach ($linhas as $l) {
    $comentario = trim(substr($l, strpos($l, '//') + 2));
    // str_word_count não enxerga acento como letra; contar por espaço é o certo
    // para português.
    if (count(array_filter(explode(' ', $comentario))) >= 3) {
        $explicadas++;
    }
}
checa('todo comentário explica, não repete', $explicadas === count($linhas),
    "{$explicadas}/" . count($linhas) . ' linhas comentadas dizem o porquê');

manual('revisar linha a linha com a IA aberta', 'exige a IDE; o código acima é o resultado revisado');

fecharPratica();
