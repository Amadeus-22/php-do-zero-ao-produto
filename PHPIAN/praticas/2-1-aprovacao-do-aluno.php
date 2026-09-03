<?php

// PHPIAN · Módulo 2 · Aula 1 — Operadores
// Prática: "Calcule se um aluno foi aprovado (nota >= 7 e frequencia >= 75)."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 2-1 — aprovação do aluno');

secao('A regra');

$aprovado = static fn (float $nota, float $frequencia): bool => $nota >= 7.0 && $frequencia >= 75.0;

$casos = [
    ['Ana',   8.5, 90.0, true,  'passa nos dois critérios'],
    ['Bruno', 9.0, 70.0, false, 'nota boa, frequência baixa'],
    ['Célia', 6.0, 95.0, false, 'presente, mas nota baixa'],
    ['Davi',  7.0, 75.0, true,  'exatamente no limite — >= inclui'],
    ['Eva',   6.9, 74.9, false, 'um décimo abaixo em cada'],
];

foreach ($casos as [$nome, $nota, $freq, $esperado, $porque]) {
    $r = $aprovado($nota, $freq);
    checa(
        sprintf('%-6s nota %.1f freq %.1f%% -> %s', $nome, $nota, $freq, $r ? 'Aprovado' : 'Reprovado'),
        $r === $esperado,
        $porque
    );
}

secao('Por que && e não &');

// && é lógico e faz curto-circuito; & é bit a bit e avaliaria os dois lados.
$avaliou = false;
$segundo = static function () use (&$avaliou): bool { $avaliou = true; return true; };
$_ = (5 >= 7) && $segundo();
checa('&& não avalia o lado direito se o esquerdo é falso', $avaliou === false, 'curto-circuito');

secao('Os outros operadores da aula');

$a = 10;
$b = 3;
checa('$a + $b === 13', $a + $b === 13);
checa('$a % $b === 1  (resto)', $a % $b === 1);
checa('$a ** $b === 1000  (potência)', $a ** $b === 1000);
checa('10 <=> 3 é 1  (nave espacial)', ($a <=> $b) === 1, 'compara e devolve -1, 0 ou 1');

// Null coalescing: o que a aula usa para não estourar em $_GET ausente.
$get = [];
checa('?? entrega o padrão quando a chave não existe', ($get['nome'] ?? 'Visitante') === 'Visitante');
$get['nome'] = 'Ana';
checa('?? entrega o valor quando existe', ($get['nome'] ?? 'Visitante') === 'Ana');

// Nullsafe: chamar método em null devolve null em vez de estourar.
$obj = null;
checa('?-> em null devolve null, não erro fatal', ($obj?->qualquerCoisa()) === null);

secao('and/or têm precedência diferente de &&/||');

// A aula avisa; este é o efeito prático, que já derrubou muito código.
$x = false || true;      // ||  liga mais forte que =  ->  $x = true
$y = false or true;      // or  liga mais fraco que =  ->  $y = false
checa('$x = false || true  ->  true', $x === true);
checa('$y = false or true  ->  false (!)', $y === false, 'o "or" roda DEPOIS do "="');

fecharPratica();
