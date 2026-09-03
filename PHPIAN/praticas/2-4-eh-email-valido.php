<?php

// PHPIAN · Módulo 2 · Aula 4 — Funções
// Prática: "Crie ehEmailValido(string $email): bool usando filter_var."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 2-4 — ehEmailValido()');

secao('A função pedida');

function ehEmailValido(string $email): bool
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

$casos = [
    ['ana@exemplo.com', true,  'caso comum'],
    ['  ana@exemplo.com  ', true, 'trim resolve espaço colado'],
    ['ana.souza+tag@sub.exemplo.com.br', true, 'ponto, mais e subdomínio são válidos'],
    ['texto sem arroba', false, 'sem @'],
    ['ana@', false, 'sem domínio'],
    ['@exemplo.com', false, 'sem parte local'],
    ['ana@@exemplo.com', false, 'dois @'],
    ['', false, 'vazio'],
];

foreach ($casos as [$entrada, $esperado, $porque]) {
    checa(
        sprintf('%-34s -> %s', '"' . $entrada . '"', $esperado ? 'válido' : 'inválido'),
        ehEmailValido($entrada) === $esperado,
        $porque
    );
}

secao('Tipagem: o que strict_types garante');

checa('declara o tipo do parâmetro e do retorno', (new ReflectionFunction('ehEmailValido'))->getReturnType()?->getName() === 'bool');
checaExcecao(
    'passar int onde se espera string lança TypeError',
    \TypeError::class,
    static fn () => ehEmailValido(42)
);

secao('Os outros pontos da aula');

// Parâmetro com valor padrão
function formatarPreco(float $valor, string $moeda = 'R$'): string
{
    return $moeda . ' ' . number_format($valor, 2, ',', '.');
}
checa('valor padrão é usado quando se omite', formatarPreco(19.9) === 'R$ 19,90');
checa('valor padrão é sobrescrito quando se passa', formatarPreco(19.9, 'US$') === 'US$ 19,90');

// Exceção em vez de retorno mágico
function dividir(float $a, float $b): float
{
    if ($b === 0.0) {
        throw new InvalidArgumentException('Divisão por zero');
    }
    return $a / $b;
}
checa('dividir(10, 2) === 5.0', dividir(10, 2) === 5.0);
checaExcecao('dividir por zero lança InvalidArgumentException', \InvalidArgumentException::class, static fn () => dividir(10, 0));

// Escopo: "variáveis locais ≠ globais"
$fora = 'de fora';
$naoEnxerga = static function (): bool { return !isset($fora); };
checa('função não enxerga variável de fora sem use()', $naoEnxerga(), 'por isso a aula pede evitar global');

// Arrow function
$dobro = fn (int $x): int => $x * 2;
checa('arrow function fn($x) => $x * 2', $dobro(21) === 42);
$fator = 3;
$triplo = fn (int $x): int => $x * $fator;
checa('arrow function captura o escopo automaticamente', $triplo(7) === 21, '$fator veio de fora sem use()');

fecharPratica();
