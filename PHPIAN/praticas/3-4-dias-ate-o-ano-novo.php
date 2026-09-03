<?php

// PHPIAN · Módulo 3 · Aula 4 — Números e datas
// Prática: "Mostre quantos dias faltam para o próximo ano-novo usando DateTime e diff()."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 3-4 — dias até o ano-novo');

secao('A função');

function diasAteOAnoNovo(?DateTimeImmutable $de = null): int
{
    $fuso = new DateTimeZone('America/Sao_Paulo');
    // Zera a hora: senão "faltam 3 dias e 7 horas" vira 3, e no dia 31 à noite
    // daria 0 quando ainda falta 1 dia.
    $hoje = ($de ?? new DateTimeImmutable('now', $fuso))->setTime(0, 0);
    $anoNovo = $hoje->setDate((int) $hoje->format('Y') + 1, 1, 1)->setTime(0, 0);

    return (int) $hoje->diff($anoNovo)->days;
}

$hoje = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
$faltam = diasAteOAnoNovo();
nota("hoje: {$hoje->format('d/m/Y')} — faltam {$faltam} dia(s) para 01/01/" . ((int) $hoje->format('Y') + 1));

checa('o resultado é positivo', $faltam > 0);
checa('cabe em um ano', $faltam <= 366);

secao('Casos fixos, que não dependem de quando isto roda');

$casos = [
    ['2026-12-31', 1,   'véspera'],
    ['2026-01-01', 365, '1º de janeiro: falta o ano inteiro (2026 não é bissexto)'],
    ['2027-01-01', 365, '2027 também não é bissexto'],
    ['2028-01-01', 366, '2028 é bissexto'],
    ['2026-12-25', 7,   'natal'],
    ['2026-07-01', 184, 'meio do ano'],
];
foreach ($casos as [$data, $esperado, $porque]) {
    $d = new DateTimeImmutable($data, new DateTimeZone('America/Sao_Paulo'));
    checa(sprintf('%s -> %d dia(s)', $data, diasAteOAnoNovo($d)), diasAteOAnoNovo($d) === $esperado, $porque);
}

secao('Por que zerar a hora');

// Sem setTime, o diff conta 0 dias completos na tarde do dia 31.
$tarde = new DateTimeImmutable('2026-12-31 22:00', new DateTimeZone('America/Sao_Paulo'));
$anoNovo = new DateTimeImmutable('2027-01-01 00:00', new DateTimeZone('America/Sao_Paulo'));
checa('sem zerar, 31/12 às 22h dá 0 dias', (int) $tarde->diff($anoNovo)->days === 0, 'faltam 2 horas');
checa('zerando, dá 1 dia', diasAteOAnoNovo($tarde) === 1, 'é a resposta que o usuário espera');

secao('Números — o resto do código da aula');

checa('abs(-5) é 5', abs(-5) === 5);
checa('round(3.14159, 2) é 3.14', round(3.14159, 2) === 3.14);
checa('round arredonda meio pra cima', round(2.5) === 3.0);
checa('intdiv(10, 3) é 3', intdiv(10, 3) === 3);

$sorteio = random_int(1, 100);
checa('random_int(1, 100) fica na faixa', $sorteio >= 1 && $sorteio <= 100, (string) $sorteio);

// "Evite rand() para tokens/senhas" — o motivo, medido.
$token = bin2hex(random_bytes(16));
checa('random_bytes(16) dá 32 hex', strlen($token) === 32, $token);
$dois = [bin2hex(random_bytes(16)), bin2hex(random_bytes(16))];
checa('dois tokens não se repetem', $dois[0] !== $dois[1]);

secao('DateTime x DateTimeImmutable');

$mutavel = new DateTime('2026-09-01', new DateTimeZone('America/Sao_Paulo'));
$copia = $mutavel;
$mutavel->modify('+7 days');
checa('DateTime::modify MUDA o objeto (e a "cópia" junto)', $copia->format('d/m/Y') === '08/09/2026',
    'a atribuição copiou a referência, não o valor');

$imutavel = new DateTimeImmutable('2026-09-01', new DateTimeZone('America/Sao_Paulo'));
$outro = $imutavel->modify('+7 days');
checa('DateTimeImmutable devolve um novo e preserva o original', $imutavel->format('d/m/Y') === '01/09/2026');
checa('e o novo tem a data alterada', $outro->format('d/m/Y') === '08/09/2026');

fecharPratica();
