<?php

// PHPIAN · Módulo 2 · Aula 2 — Condicionais
// Prática: "Com match, converta o código HTTP 200, 404, 500 em mensagens amigáveis."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 2-2 — status HTTP com match');

secao('O match pedido');

$mensagem = static fn (int $status): string => match ($status) {
    200 => 'Tudo certo.',
    404 => 'Não encontramos essa página.',
    500 => 'Erro do nosso lado. Já estamos vendo.',
    default => 'Resposta inesperada do servidor.',
};

foreach ([200 => 'Tudo certo.', 404 => 'Não encontramos essa página.', 500 => 'Erro do nosso lado. Já estamos vendo.'] as $codigo => $esperado) {
    checa("{$codigo} -> \"{$esperado}\"", $mensagem($codigo) === $esperado);
}
checa('418 cai no default', $mensagem(418) === 'Resposta inesperada do servidor.');

secao('"match é estrito" — a diferença que a aula destaca');

// switch usa ==; match usa ===. Com a string "200" isso muda o resultado.
$viaSwitch = static function (mixed $s): string {
    switch ($s) {
        case 200: return 'casou com 200';
        default:  return 'caiu no default';
    }
};
checa('switch("200") casa com o int 200 (coerção)', $viaSwitch('200') === 'casou com 200');
checaExcecao(
    'match("200") não casa e lança UnhandledMatchError',
    \UnhandledMatchError::class,
    static fn () => match ('200') { 200 => 'x' }
);

secao('"match retorna valor e não precisa de break"');

$faixa = static fn (int $s): string => match (true) {
    $s >= 200 && $s < 300 => 'sucesso',
    $s >= 300 && $s < 400 => 'redirecionamento',
    $s >= 400 && $s < 500 => 'erro do cliente',
    $s >= 500 => 'erro do servidor',
    default => 'informativo',
};

foreach ([200 => 'sucesso', 302 => 'redirecionamento', 404 => 'erro do cliente', 500 => 'erro do servidor', 100 => 'informativo'] as $s => $esperado) {
    checa(sprintf('%d é %s', $s, $esperado), $faixa($s) === $esperado);
}

secao('O if/elseif/else da aula, com o mesmo resultado');

$notaTexto = static function (float $nota): string {
    if ($nota >= 7) {
        return 'Aprovado';
    } elseif ($nota >= 5) {
        return 'Recuperação';
    }
    return 'Reprovado';
};
$notaMatch = static fn (float $n): string => match (true) {
    $n >= 7 => 'Aprovado',
    $n >= 5 => 'Recuperação',
    default => 'Reprovado',
};

foreach ([8.5, 6.0, 3.0, 7.0, 5.0] as $n) {
    checa(sprintf('nota %.1f: if e match concordam', $n), $notaTexto($n) === $notaMatch($n), $notaMatch($n));
}

secao('O callout do ternário');

$logado = true;
checa('ternário simples é legível', ($logado ? 'Olá' : 'Entre') === 'Olá');
// A aula pede parcimônia: ternário aninhado exige parênteses desde o PHP 8 e
// continua ilegível mesmo com eles. Fica registrado como o que NÃO fazer.
nota('ternário aninhado sem parênteses é erro fatal no PHP 8 — a aula está certa em pedir parcimônia');

fecharPratica();
