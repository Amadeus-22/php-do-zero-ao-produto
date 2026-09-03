<?php

// PHPIAN · Módulo 1 · Aula 1 — PHP moderno no mapa da web
// Prática: "Anote em uma frase: 'Quero publicar um sistema PHP que faz ___.'"

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 1-1 — o norte do curso');

secao('A frase');

// A frase é decisão pessoal do aluno; o que dá para verificar é que ela existe,
// está preenchida e não ficou com o "___" do modelo.
$norte = 'Quero publicar um sistema PHP que faz a gestão de contatos de um negócio pequeno, '
    . 'com login por usuário e dados no MySQL.';

nota($norte);

checa('a frase foi escrita', trim($norte) !== '');
checa('não sobrou o "___" do modelo', !str_contains($norte, '___'));
checa('começa como o exercício pede', str_starts_with($norte, 'Quero publicar um sistema PHP que faz'));
checa('descreve algo concreto (mais de 5 palavras depois do "faz")',
    count(array_filter(explode(' ', substr($norte, strpos($norte, 'faz') + 3)))) > 5);

secao('O norte bate com o projeto final?');

// O Módulo 8 entrega um CRM de contatos com login. A frase acima aponta para lá.
foreach (['contatos' => 'CRUD de contatos', 'login' => 'área autenticada', 'MySQL' => 'persistência'] as $palavra => $oQue) {
    checa("menciona {$oQue}", stripos($norte, $palavra) !== false, "\"{$palavra}\"");
}

fecharPratica();
