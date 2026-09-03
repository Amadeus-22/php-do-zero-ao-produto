<?php

// PHPIAN · Módulo 5 · Aula 4 — Git e GitHub sem drama
// Prática: "Coloque o projeto de contato no Git, crie o repo no GitHub e faça o
// primeiro push."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 5-4 — Git e GitHub');

$raiz = areaTemporaria('5-4');

$git = static function (string $args, string $dir): array {
    exec(sprintf('cd %s && git %s 2>&1', escapeshellarg($dir), $args), $saida, $codigo);
    return ['saida' => implode("\n", $saida), 'codigo' => $codigo];
};

secao('git init e o primeiro commit');

// Identidade só deste repositório: não mexe na configuração global da máquina.
$git('init -q -b main', $raiz);
$git('config user.name "Prática PHPIAN"', $raiz);
$git('config user.email "pratica@exemplo.local"', $raiz);

checa('o repositório foi criado', is_dir($raiz . '/.git'));
checa('o branch padrão é main', trim($git('branch --show-current', $raiz)['saida']) === 'main');

// O projeto de contato da aula 4-5
mkdir($raiz . '/storage/uploads', 0777, true);
file_put_contents($raiz . '/contato.php', "<?php\n// projeto relâmpago da aula 4-5\n");
file_put_contents($raiz . '/README.md', "# Contato PHPIAN\n\nFormulário de contato com PRG.\n");

secao('O .gitignore essencial da aula');

file_put_contents($raiz . '/.gitignore', <<<'TXT'
/vendor/
.env
/storage/uploads/*
!/storage/uploads/.gitkeep
mensagens.txt
info.php
TXT);
file_put_contents($raiz . '/storage/uploads/.gitkeep', '');

// Tudo que a aula manda NÃO subir
mkdir($raiz . '/vendor');
file_put_contents($raiz . '/vendor/autoload.php', "<?php\n");
file_put_contents($raiz . '/.env', "DB_PASS=senha-real-de-producao\n");
file_put_contents($raiz . '/mensagens.txt', "dados de quem escreveu\n");
file_put_contents($raiz . '/info.php', "<?php phpinfo();\n");
file_put_contents($raiz . '/storage/uploads/foto-de-usuario.png', 'binário');

$git('add .', $raiz);
$git('commit -q -m "Primeira versão do contato"', $raiz);

$versionados = explode("\n", trim($git('ls-files', $raiz)['saida']));
foreach ($versionados as $v) {
    nota($v);
}

checa('o commit foi criado', $git('rev-parse HEAD', $raiz)['codigo'] === 0);
checa('a mensagem é a da aula', str_contains($git('log -1 --pretty=%s', $raiz)['saida'], 'Primeira versão do contato'));
checa('contato.php entrou', in_array('contato.php', $versionados, true));
checa('README.md entrou', in_array('README.md', $versionados, true));

secao('"Nunca suba" — conferido arquivo por arquivo');

foreach ([
    '.env' => 'senha de produção',
    'vendor/autoload.php' => 'dependências baixadas',
    'mensagens.txt' => 'dados de usuários',
    'info.php' => 'expõe a config do PHP',
    'storage/uploads/foto-de-usuario.png' => 'upload de terceiro',
] as $arquivo => $porque) {
    checa("{$arquivo} ficou de fora", !in_array($arquivo, $versionados, true), $porque);
}
checa('.gitkeep entrou (a exceção com "!")', in_array('storage/uploads/.gitkeep', $versionados, true),
    'mantém a pasta no repositório sem os arquivos dentro');

secao('O fluxo do dia a dia');

file_put_contents($raiz . '/contato.php', "<?php\n// projeto relâmpago da aula 4-5\n// validação adicionada\n");
$status = $git('status --porcelain', $raiz)['saida'];
checa('git status mostra o arquivo modificado', str_contains($status, 'contato.php'), trim($status));

$git('add contato.php', $raiz);
$git('commit -q -m "Valida o formulário antes de gravar"', $raiz);
$log = $git('log --oneline', $raiz)['saida'];
checa('git log --oneline mostra os 2 commits', substr_count($log, "\n") === 1, str_replace("\n", ' | ', $log));
checa('a árvore ficou limpa', trim($git('status --porcelain', $raiz)['saida']) === '');

secao('O push — simulado com um remoto local');

// Um repositório bare no disco se comporta como o GitHub para push/pull. O que
// não dá para automatizar é criar a conta e o repo remoto de verdade.
$remoto = $raiz . '-remoto.git';
exec(sprintf('git init -q --bare %s 2>&1', escapeshellarg($remoto)));
$git('remote add origin ' . escapeshellarg($remoto), $raiz);
$push = $git('push -q -u origin main', $raiz);

checa('o remoto foi registrado como origin', str_contains($git('remote -v', $raiz)['saida'], 'origin'));
checa('o push funcionou', $push['codigo'] === 0, $push['saida']);
checa('o remoto tem o mesmo commit', trim($git('rev-parse HEAD', $raiz)['saida']) === trim($git('rev-parse origin/main', $raiz)['saida']));
checa('o branch local rastreia o remoto', str_contains($git('status -sb', $raiz)['saida'], 'origin/main'), '-u fez isso');

// E o segredo continua fora, inclusive no remoto
exec(sprintf('git --git-dir=%s ls-tree -r --name-only HEAD 2>&1', escapeshellarg($remoto)), $noRemoto);
checa('o .env NÃO chegou ao remoto', !in_array('.env', $noRemoto, true), 'era o ponto do exercício');

limparArea($remoto);

manual('criar o repositório real no github.com e colar o link no README', 'exige conta e credencial');

fecharPratica();
