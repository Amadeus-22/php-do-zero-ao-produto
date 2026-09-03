<?php

// PHPIAN · Módulo 8 · Aula 5 — Rodar no localhost e publicar online
// Prática: "1) Grave um README: como instalar no localhost. 2) Se publicar, cole
// a URL do CRM no ar e teste o login."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 8-5 — README e checklist de publicação');

$mini = __DIR__ . '/../Modulo_8(modeagem_final)/mini-crm';

secao('Parte 1 — o README de instalação');

$readme = $mini . '/README.md';
checa('README.md existe', is_file($readme));
$texto = is_file($readme) ? (string) file_get_contents($readme) : '';

// O que um README de instalação precisa responder para alguém que nunca viu o projeto.
$perguntas = [
    'o que é o projeto' => ['Mini CRM', 'CRM'],
    'requisitos de PHP' => ['PHP 8', 'php 8'],
    'como criar o banco' => ['schema.sql', 'mysql', 'MySQL', 'banco'],
    'como configurar' => ['config', '.example', 'app.php'],
    'como subir o servidor' => ['php -S', 'localhost', 'Apache'],
    'como entrar' => ['seed', 'login', 'admin@'],
];
foreach ($perguntas as $pergunta => $marcas) {
    $achou = false;
    foreach ($marcas as $m) {
        if (str_contains($texto, $m)) {
            $achou = true;
            break;
        }
    }
    checa("responde: {$pergunta}", $achou, $achou ? '' : 'faltando no README');
}
checa('o README tem conteúdo real', strlen($texto) > 400, strlen($texto) . ' bytes');
checa('tem bloco de comandos', str_contains($texto, '```'), 'dá para copiar e colar');

secao('Parte 1b — o checklist localhost da aula, conferido de verdade');

// "A) Checklist localhost (obrigatório)" — cada item testado, não só lido.
checa('PHP 8.2 ou superior', version_compare(PHP_VERSION, '8.2.0', '>='), 'PHP ' . PHP_VERSION);
checa('pdo_mysql disponível', extension_loaded('pdo_mysql'));

$pdo = bancoDaPratica();
checa('o banco responde', (int) $pdo->query('SELECT 1')->fetchColumn() === 1);

// As tabelas que a 8-2 e a 8-3 deixaram prontas
$tabelas = array_column($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM), 0);
checa('tabela users existe', in_array('users', $tabelas, true));
checa('tabela contatos existe', in_array('contatos', $tabelas, true));
checa('há um usuário para entrar', (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0);

// "Sem info.php ou arquivos de debug na pasta pública"
$publica = $mini . '/public';
$debug = [];
foreach ((array) glob($publica . '/{,*/}{info,phpinfo,test,teste,debug}.php', GLOB_BRACE) as $f) {
    $debug[] = basename($f);
}
checa('nenhum info.php/debug em public/', $debug === [], implode(', ', $debug));

// "URL local abre"
$porta = 8788;
$servidor = proc_open(
    sprintf('exec php -S 127.0.0.1:%d -t %s', $porta, escapeshellarg($publica)),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes
);
usleep(900000);

$ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
@file_get_contents("http://127.0.0.1:{$porta}/login.php", false, $ctx);
$status = $http_response_header[0] ?? '';
checa('a tela de login responde por HTTP', $status !== '' && !str_contains($status, '404'), trim($status));

// A config real não existe aqui (é ignorada pelo Git), então a página pode dar
// erro de conexão — o que importa é que o servidor serve o arquivo, e que nada
// fora de public/ é alcançável.
@file_get_contents("http://127.0.0.1:{$porta}/../config/app.php", false, $ctx);
checa('config/ NÃO é alcançável por URL', !str_contains($http_response_header[0] ?? '', '200'));
@file_get_contents("http://127.0.0.1:{$porta}/../src/auth.php", false, $ctx);
checa('src/ NÃO é alcançável por URL', !str_contains($http_response_header[0] ?? '', '200'));

if (is_resource($servidor)) {
    proc_terminate($servidor);
    proc_close($servidor);
}

secao('Parte 2 — publicar online');

// O passo a passo da HostGator exige conta, cPanel e domínio. O que dá para
// verificar é se o projeto está PRONTO para subir sem vazar nada.
manual('contratar a HostGator, subir por FTP e apontar o domínio', 'exige conta e servidor');
manual('colar a URL do CRM no ar e testar o login lá', 'depende da publicação acima');

secao('Mas o projeto está pronto para subir?');

$gitignore = (string) @file_get_contents($mini . '/.gitignore');
checa('config real fora do Git', str_contains($gitignore, 'app.php'), 'senha de produção não sobe');
checa('config/app.example.php existe', is_file($mini . '/config/app.example.php'), 'o modelo sobe, o real não');

$exemplo = (string) @file_get_contents($mini . '/config/app.example.php');
checa('o .example não tem senha preenchida',
    !preg_match('/(senha|pass)[^\n]*=>\s*[\'"][^\'"]{4,}[\'"]/i', $exemplo));

// "Desligue debug: display_errors = Off"
$configExemplo = @include $mini . '/config/app.example.php';
if (is_array($configExemplo)) {
    $debugPadrao = $configExemplo['debug'] ?? $configExemplo['app']['debug'] ?? null;
    checa('o modelo declara a chave debug', array_key_exists('debug', $configExemplo));
    // O modelo vem com debug => true e o comentário "true só em localhost. Em
    // produção: false." É escolha consciente, mas quem copia o exemplo para o
    // servidor sobe com stack trace ligado. Fica registrado em DIVERGENCIAS.md.
    if ($debugPadrao === true) {
        nota('ATENÇÃO: app.example.php vem com debug => true — desligar antes de publicar');
    }
    $reg = $configExemplo['allow_registration'] ?? null;
    if ($reg === true) {
        nota('ATENÇÃO: app.example.php vem com allow_registration => true — qualquer visitante se cadastra');
    }
}

checa('sql/schema.sql pronto para importar no phpMyAdmin', is_file($mini . '/sql/schema.sql'));
checa('public/ é a pasta a apontar como raiz', is_dir($publica), 'ou subir o conteúdo dela para public_html');

secao('O aviso da aula');

$suspeito = [];
foreach ((array) glob($mini . '/**/*.php') as $f) {
    if (preg_match('/(senha|password)\s*=>\s*[\'"][^\'"]{6,}[\'"]/i', (string) file_get_contents($f))
        && !str_contains($f, 'example')) {
        $suspeito[] = basename($f);
    }
}
checa('nenhuma senha de banco no código que vai para o Git', $suspeito === [], implode(', ', $suspeito));
nota('a aula é enfática: em produção, config fora do versionamento ou variáveis do painel');

secao('C) Depois do PHPIAN');

// O roadmap da aula, conferido contra o que existe na pasta ao lado.
$phpan = __DIR__ . '/../../PHPAN';
checa('Composer + PSR-4: o PHPAN já usa', is_file($phpan . '/crm-produto/composer.json'));
checa('APIs JSON: o PHPAN entregou /api/v1', is_dir($phpan . '/crm-produto/src/Http'));
nota('Laravel é o próximo salto — o PHPPRO na grade da plataforma');

fecharPratica();
