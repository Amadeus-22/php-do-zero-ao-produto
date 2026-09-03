<?php

// PHPIAN · Módulo 8 · Aula 4 — Login e área protegida
// Prática: "Faça um checklist de segurança e marque cada item no seu projeto."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 8-4 — checklist de segurança do Mini CRM');

$mini = __DIR__ . '/../Modulo_8(modeagem_final)/mini-crm';
if (!is_dir($mini)) {
    checa('projeto mini-crm encontrado', false, $mini);
    fecharPratica();
}

// Lê o projeto inteiro uma vez; cada item do checklist procura sua evidência.
$fontes = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mini, FilesystemIterator::SKIP_DOTS));
foreach ($iter as $f) {
    if (in_array($f->getExtension(), ['php', 'sql'], true)) {
        $fontes[str_replace($mini . '/', '', $f->getPathname())] = (string) file_get_contents($f->getPathname());
    }
}
$tudo = implode("\n", $fontes);

/** Procura uma marca no projeto e reporta em qual arquivo apareceu. */
$item = static function (string $rotulo, string $marca, string $porque) use ($fontes): void {
    $onde = [];
    foreach ($fontes as $arquivo => $codigo) {
        if (str_contains($codigo, $marca)) {
            $onde[] = $arquivo;
        }
    }
    checa($rotulo, $onde !== [], $onde === [] ? "AUSENTE ({$porque})" : $onde[0]);
};

secao('Autenticação');

$item('senha com password_hash', 'password_hash', 'senha em texto puro');
$item('verificação com password_verify', 'password_verify', 'comparação insegura');
$item('rehash automático', 'password_needs_rehash', 'algoritmo nunca é atualizado');
$item('session_regenerate_id no login', 'session_regenerate_id', 'permite fixação de sessão');

// Erro genérico: não pode revelar se o e-mail existe
$login = $fontes['public/login.php'] ?? '';
$temGenerico = str_contains($login, 'ou senha') || str_contains($login, 'E-mail ou senha');
checa('erro de login é genérico', $temGenerico, 'não revela quais e-mails existem');
checa('não diz "e-mail não encontrado"', !preg_match('/e-?mail (não|nao) (encontrado|existe)/i', $login));

secao('Autorização');

$item('gate de autenticação', 'requireAuth', 'páginas protegidas sem guarda');
$contatos = implode("\n", array_filter($fontes, static fn ($k) => str_contains($k, 'contatos/'), ARRAY_FILTER_USE_KEY));
checa('as queries de contato filtram por user_id', substr_count($contatos . ($fontes['src/contatos.php'] ?? ''), 'user_id') >= 3,
    'ownership: um usuário não alcança o contato de outro');

secao('Injeção e escape');

$item('prepared statements', '->prepare(', 'SQL montado por concatenação');
$item('escape na saída', 'htmlspecialchars', 'XSS');

// O oposto: procurar o que NÃO pode existir
$interpolaSql = (bool) preg_match('/(query|exec)\s*\(\s*["\'][^"\']*\$/', $tudo);
checa('nenhuma query com variável interpolada', !$interpolaSql, 'seria SQL injection');
checa('nenhum mysql_* antigo', !preg_match('/\bmysql_(query|connect|real_escape)/', $tudo));
checa('nenhum eval()', !preg_match('/\beval\s*\(/', $tudo));
checa('nenhum erro silenciado com @', !preg_match('/@(file_get_contents|include|require|unlink|fopen)/', $tudo),
    'a aula 5-6 pede não silenciar');

secao('CSRF');

$item('token CSRF', 'csrf', 'formulário aceita POST de outro site');
$item('comparação em tempo constante', 'hash_equals', 'timing attack no token');
$item('token com random_bytes', 'random_bytes', 'token previsível');

// Toda mutação precisa do guarda
$mutacoes = ['public/contatos/criar.php', 'public/contatos/editar.php', 'public/contatos/excluir.php'];
foreach ($mutacoes as $m) {
    checa(basename($m) . ' valida CSRF', str_contains($fontes[$m] ?? '', 'csrf'), $m);
}

secao('Configuração e segredos');

checa('config de exemplo existe', is_file($mini . '/config/app.example.php'), 'app.example.php');
$gitignore = (string) @file_get_contents($mini . '/.gitignore');
checa('.gitignore existe', $gitignore !== '');
checa('config real está ignorada', str_contains($gitignore, 'config/app.php') || str_contains($gitignore, 'app.php'),
    'senha de banco fora do Git');

// Nenhuma senha literal no código versionado
$suspeitas = [];
foreach ($fontes as $arquivo => $codigo) {
    if (str_contains($arquivo, 'app.php') && !str_contains($arquivo, 'example')) {
        continue;   // esse é o arquivo ignorado pelo Git, e é onde a senha DEVE estar
    }
    if (preg_match('/(senha|password|pass)\s*=>\s*[\'"][^\'"]{6,}[\'"]/i', $codigo, $m)) {
        $suspeitas[] = $arquivo;
    }
}
checa('nenhuma senha literal no código versionado', $suspeitas === [], implode(', ', $suspeitas));

secao('Estrutura');

checa('public/ é a raiz web', is_dir($mini . '/public'));
foreach (['src', 'config', 'sql'] as $pasta) {
    checa("{$pasta}/ está fora de public/", is_dir($mini . '/' . $pasta) && !is_dir($mini . '/public/' . $pasta),
        'inalcançável por URL');
}

secao('O SEGURANCA.md que o projeto guarda');

$doc = $mini . '/SEGURANCA.md';
checa('SEGURANCA.md existe', is_file($doc));
if (is_file($doc)) {
    $texto = (string) file_get_contents($doc);
    $marcados = substr_count($texto, '- [x]');
    $abertos = substr_count($texto, '- [ ]');
    checa('tem itens marcados', $marcados > 0, "{$marcados} marcados · {$abertos} em aberto");
    foreach (['password_hash', 'CSRF', 'PDO'] as $tema) {
        checa("documenta {$tema}", stripos($texto, $tema) !== false);
    }
}

fecharPratica();
