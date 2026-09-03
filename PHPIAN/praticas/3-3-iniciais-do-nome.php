<?php

// PHPIAN · Módulo 3 · Aula 3 — Manipulação de strings
// Prática: "Crie uma função que recebe um nome completo e retorna as iniciais em
// maiúsculo (ex.: 'Ana Silva' → 'AS')."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 3-3 — iniciais do nome');

secao('A função');

function iniciais(string $nomeCompleto): string
{
    // preg_split com PREG_SPLIT_NO_EMPTY resolve espaço duplo e espaço nas pontas
    // de uma vez; explode(' ') deixaria strings vazias no meio.
    $partes = preg_split('/\s+/u', trim($nomeCompleto), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    $letras = '';
    foreach ($partes as $parte) {
        // mb_* porque a aula avisa: strtoupper quebra acento, mb_strtoupper não.
        $letras .= mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8');
    }

    return $letras;
}

$casos = [
    ['Ana Silva', 'AS', 'o exemplo da aula'],
    ['ana silva', 'AS', 'minúsculo vira maiúsculo'],
    ['  Ana   Silva  ', 'AS', 'espaço extra e nas pontas'],
    ['Ana Maria Souza Lima', 'AMSL', 'quatro nomes'],
    ['Ágata Ésio Índio Óscar Úrsula', 'ÁÉÍÓÚ', 'acentos preservados — é o que mb_* garante'],
    ['Ana', 'A', 'um nome só'],
    ['', '', 'string vazia'],
    ['   ', '', 'só espaços'],
    ['Çelso Ñuñez', 'ÇÑ', 'cedilha e til'],
];

foreach ($casos as [$entrada, $esperado, $porque]) {
    $r = iniciais($entrada);
    checa(sprintf('%-32s -> "%s"', '"' . $entrada . '"', $r), $r === $esperado, $porque);
}

secao('Por que mb_* — a armadilha que a aula cita');

checa('strlen("ação") é 6 (bytes)', strlen('ação') === 6, 'ç e ã ocupam 2 bytes cada');
checa('mb_strlen("ação") é 4 (letras)', mb_strlen('ação', 'UTF-8') === 4);
checa('strtoupper("ação") NÃO sobe o ç', strtoupper('ação') !== 'AÇÃO', strtoupper('ação'));
checa('mb_strtoupper("ação") sobe tudo', mb_strtoupper('ação', 'UTF-8') === 'AÇÃO');
checa('substr corta no meio do byte', substr('ação', 0, 2) !== 'aç', 'devolve lixo: ' . bin2hex(substr('ação', 0, 2)));
checa('mb_substr corta na letra', mb_substr('ação', 0, 2, 'UTF-8') === 'aç');

secao('O resto do código da aula');

$texto = '  PHP Iniciante  ';
checa('trim tira as pontas', trim($texto) === 'PHP Iniciante');
checa('strtoupper sem acento funciona', strtoupper(trim($texto)) === 'PHP INICIANTE');

$slug = str_replace(' ', '-', strtolower(trim($texto)));
checa('slug simples', $slug === 'php-iniciante');

// O slug da aula não trata acento; com acento ele vaza para a URL.
$slugAcentuado = str_replace(' ', '-', strtolower(trim('  Ação Rápida  ')));
checa('slug da aula deixa acento passar', $slugAcentuado === 'ação-rápida', $slugAcentuado);
$slugLimpo = preg_replace('/[^a-z0-9]+/', '-', trim(strtolower(
    (string) iconv('UTF-8', 'ASCII//TRANSLIT', 'Ação Rápida')
)));
checa('com iconv//TRANSLIT vira ascii', trim((string) $slugLimpo, '-') === 'acao-rapida', (string) $slugLimpo);

fecharPratica();
