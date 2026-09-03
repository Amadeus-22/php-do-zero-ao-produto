<?php

// PHPIAN · Módulo 1 · Aula 5 — Variáveis, tipos e constantes
// Prática: "Declare variáveis de um produto (nome, preço, estoque) e imprima:
// '{$nome} custa R$ {$preco}'."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 1-5 — produto em variáveis');

secao('As três variáveis');

$nome = 'Teclado mecânico';
$preco = 349.90;
$estoque = 12;

checa('nome é string', is_string($nome), gettype($nome) . ": {$nome}");
checa('preco é float', is_float($preco), gettype($preco) . ": {$preco}");
checa('estoque é int', is_int($estoque), gettype($estoque) . ": {$estoque}");

secao('A saída pedida');

$saida = "{$nome} custa R$ {$preco}";
nota($saida);
checa('interpolação montou a frase', $saida === 'Teclado mecânico custa R$ 349.9');

// O 349.9 mostra por que a aula 3-3 vai ensinar number_format: interpolação crua
// come o zero final e usa ponto, não vírgula.
$formatado = "{$nome} custa R$ " . number_format($preco, 2, ',', '.');
nota($formatado);
checa('com number_format vira preço de verdade', str_ends_with($formatado, 'R$ 349,90'));

secao('Constantes');

define('APP_NOME', 'PHPIAN');
const VERSAO = '1.0';

checa('define() criou APP_NOME', defined('APP_NOME') && APP_NOME === 'PHPIAN');
checa('const criou VERSAO', VERSAO === '1.0');
// define() sobre constante já definida NÃO lança: emite Warning e devolve false.
$aviso = null;
set_error_handler(static function (int $n, string $msg) use (&$aviso): bool { $aviso = $msg; return true; });
$redefiniu = define('VERSAO', '2.0');
restore_error_handler();
checa('define() sobre constante existente devolve false', $redefiniu === false);
checa('e emite Warning "already defined"', str_contains((string) $aviso, 'already defined'), (string) $aviso);
checa('o valor original permanece', VERSAO === '1.0');

secao('O callout: === contra ==');

checa('"0" == false é true (coerção)', ('0' == false) === true, 'a armadilha que a aula cita');
checa('"0" === false é false (estrito)', ('0' === false) === false, 'por isso se usa ===');
checa('estoque > 0 decide se está disponível', $estoque > 0, "{$estoque} em estoque");

fecharPratica();
