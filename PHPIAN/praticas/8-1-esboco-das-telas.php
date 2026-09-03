<?php

// PHPIAN · Módulo 8 · Aula 1 — Briefing do Mini CRM
// Prática: "Esboce no papel as telas: Login, Lista, Novo, Editar. Anote os campos
// de cada formulário."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 8-1 — esboço das telas');

secao('O esboço, escrito em vez de desenhado');

// O papel não é versionável nem verificável. O mesmo conteúdo — as telas e os
// campos de cada formulário — vira estrutura, e aí dá para conferir contra o
// projeto que o Módulo 8 entregou.
$telas = [
    'Login' => [
        'arquivo' => 'public/login.php',
        'campos' => ['email', 'senha'],
        'acoes' => ['Entrar'],
        'acesso' => 'público',
    ],
    'Lista' => [
        'arquivo' => 'public/contatos/index.php',
        'campos' => ['busca'],
        'acoes' => ['Novo contato', 'Editar', 'Excluir', 'Buscar'],
        'acesso' => 'autenticado',
    ],
    'Novo' => [
        'arquivo' => 'public/contatos/criar.php',
        'campos' => ['nome', 'email', 'telefone', 'notas'],
        'acoes' => ['Salvar', 'Cancelar'],
        'acesso' => 'autenticado',
    ],
    'Editar' => [
        'arquivo' => 'public/contatos/editar.php',
        'campos' => ['nome', 'email', 'telefone', 'notas'],
        'acoes' => ['Salvar', 'Cancelar'],
        'acesso' => 'autenticado',
    ],
];

foreach ($telas as $nome => $t) {
    nota(sprintf('%-7s %-30s campos: %s', $nome, $t['arquivo'], implode(', ', $t['campos'])));
}

checa('as 4 telas do briefing estão esboçadas', array_keys($telas) === ['Login', 'Lista', 'Novo', 'Editar']);
checa('cada tela tem campos anotados', !array_filter($telas, static fn ($t) => $t['campos'] === []));
checa('cada tela tem ações anotadas', !array_filter($telas, static fn ($t) => $t['acoes'] === []));
checa('só o Login é público', count(array_filter($telas, static fn ($t) => $t['acesso'] === 'público')) === 1);

secao('Os campos batem com o briefing');

// Briefing: "CRUD de contatos (nome, e-mail, telefone, notas)"
checa('Novo tem os 4 campos do briefing', $telas['Novo']['campos'] === ['nome', 'email', 'telefone', 'notas']);
checa('Editar tem os mesmos campos de Novo', $telas['Editar']['campos'] === $telas['Novo']['campos'],
    'mesmo formulário, um com dados preenchidos');
checa('Login pede e-mail e senha', $telas['Login']['campos'] === ['email', 'senha']);
checa('Lista tem busca', in_array('busca', $telas['Lista']['campos'], true), 'o briefing pede "listagem com busca"');

secao('O esboço bate com o projeto entregue');

$mini = __DIR__ . '/../Modulo_8(modeagem_final)/mini-crm';
if (!is_dir($mini)) {
    manual('conferir contra o mini-crm', 'pasta do projeto não encontrada');
    fecharPratica();
}

foreach ($telas as $nome => $t) {
    checa("{$nome} existe no projeto", is_file($mini . '/' . $t['arquivo']), $t['arquivo']);
}

foreach (['Novo' => 'criar.php', 'Editar' => 'editar.php'] as $tela => $arquivo) {
    $codigo = (string) @file_get_contents($mini . '/public/contatos/' . $arquivo);
    foreach ($telas[$tela]['campos'] as $campo) {
        checa("{$tela}: campo \"{$campo}\" no HTML", str_contains($codigo, "\"{$campo}\"") || str_contains($codigo, "'{$campo}'"));
    }
}

$login = (string) @file_get_contents($mini . '/public/login.php');
checa('Login: campo email', str_contains($login, 'email'));
checa('Login: campo senha', str_contains($login, 'senha'));

$lista = (string) @file_get_contents($mini . '/public/contatos/index.php');
checa('Lista: tem busca', stripos($lista, 'busca') !== false || stripos($lista, 'LIKE') !== false);

secao('O TELAS.md que o projeto já guarda');

$doc = $mini . '/TELAS.md';
checa('TELAS.md existe', is_file($doc));
if (is_file($doc)) {
    $texto = (string) file_get_contents($doc);
    foreach (['Login', 'Lista', 'Novo', 'Editar'] as $tela) {
        checa("TELAS.md descreve a tela {$tela}", stripos($texto, $tela) !== false);
    }
}

fecharPratica();
