<?php

// PHPAN · Módulo 8 · Aula 04 — Hardening final
// metadados em aulas.json · a ideia em 04-hardening-final.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Http\Middleware\SecurityHeaders;

$raiz = __DIR__ . '/../crm-produto';
bancoDaAula();

$fontesSrc = static function () use ($raiz): array {
    $arquivos = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz . '/src'));
    foreach ($it as $f) {
        if ($f instanceof SplFileInfo && $f->getExtension() === 'php') {
            $arquivos[(string) $f->getPathname()] = php_strip_whitespace((string) $f->getPathname());
        }
    }

    return $arquivos;
};
$fontes = $fontesSrc();

titulo('Aula 4 — Hardening final');

secao('Não é técnica nova: é VERIFICAR o que já deveria estar feito');

secao('1. SQL injection');

/**
 * Detecção por TOKEN, não por regex: procura string com interpolação de verdade
 * (aspas duplas com variável dentro) que contenha palavra-chave SQL.
 *
 * A primeira versão disto usava regex sobre o arquivo inteiro e acusou 13
 * arquivos inocentes — a variável estava fora da string, no $stmt->execute().
 * Regex não distingue "dentro da string" de "na linha de baixo".
 */
$interpoladas = static function (string $arquivo): bool {
    $tokens = token_get_all((string) file_get_contents($arquivo));
    $dentroDeString = false;
    $temSql = false;

    foreach ($tokens as $token) {
        if ($token === '"') {
            // abre ou fecha string com interpolação
            if ($dentroDeString && $temSql) {
                return true;
            }

            $dentroDeString = !$dentroDeString;
            $temSql = false;
            continue;
        }

        if (!is_array($token) || !$dentroDeString) {
            continue;
        }

        if ($token[0] === T_ENCAPSED_AND_WHITESPACE
            && preg_match('/\b(SELECT|INSERT INTO|UPDATE|DELETE FROM)\b/i', $token[1]) === 1) {
            $temSql = true;
        }
    }

    return false;
};

$interpolacao = [];
foreach (array_keys($fontes) as $arquivo) {
    if ($interpoladas($arquivo)) {
        $interpolacao[] = basename($arquivo);
    }
}

// Existem DOIS casos de interpolação no projeto, e os dois são deliberados:
// nome de coluna e LIMIT/OFFSET não podem ser parâmetro preparado. Em vez de
// fingir que não existem, a verificação exige a defesa específica de cada um.
sort($interpolacao);
checa(
    'a interpolação é só nos 2 casos conhecidos',
    $interpolacao === ['PlanLimiter.php', 'RepositorioDeClientesPdo.php'],
    implode(', ', $interpolacao),
);

$limiter = $fontes[$raiz . '/src/Billing/PlanLimiter.php'];
checa(
    'PlanLimiter: nome de coluna passa por whitelist',
    str_contains($limiter, "in_array(\$coluna, ['max_clientes', 'max_usuarios'], true)"),
    'valor fora da lista lança exceção',
);

$repo = $fontes[$raiz . '/src/Infrastructure/Cliente/RepositorioDeClientesPdo.php'];
checa(
    'Repositório: LIMIT/OFFSET via sprintf %d',
    str_contains($repo, "sprintf(' LIMIT %d OFFSET %d'"),
    'o %d garante inteiro, não texto do usuário',
);
checa(
    'e todo VALOR continua parametrizado',
    str_contains($repo, ':q_nome') && str_contains($repo, "execute(\$params)"),
    'nenhum dado de usuário concatenado',
);

$db = php_strip_whitespace($raiz . '/src/Support/Database.php');
checa('EMULATE_PREPARES desligado', str_contains($db, 'ATTR_EMULATE_PREPARES => false'), 'quem prepara é o MySQL, não o PHP');

secao('2. XSS');

$views = array_merge(glob($raiz . '/views/**/*.php') ?: [], glob($raiz . '/views/*.php') ?: []);
$semEscape = [];

foreach ($views as $view) {
    foreach (explode("\n", (string) file_get_contents($view)) as $numero => $linha) {
        if (preg_match_all('/<\?=\s*(.+?)\s*\?>/', $linha, $m) === 0) {
            continue;
        }

        foreach ($m[1] as $expressao) {
            // seguro: escapado, com cast numérico, ou só literais (ternário de texto)
            $seguro = str_contains($expressao, 'View::e')
                || str_contains($expressao, '(int)')       // cast numérico
                || str_contains($expressao, '$content')    // HTML já renderizado
                || !str_contains($expressao, '$')          // literal puro
                // ternário cujos DOIS ramos são literais: a variável decide qual
                // texto sai, mas o texto nunca vem do usuário
                || preg_match("/\?\s*'[^']*'\s*:\s*'[^']*'/", $expressao) === 1;

            if (!$seguro) {
                $semEscape[] = basename($view) . ':' . ($numero + 1);
            }
        }
    }
}
checa('toda saída de view passa por View::e()', $semEscape === [], $semEscape === [] ? count($views) . ' views verificadas' : implode(', ', $semEscape));

$viewFonte = php_strip_whitespace($raiz . '/src/Support/View.php');
checa('o escape usa ENT_QUOTES', str_contains($viewFonte, 'ENT_QUOTES'), 'sem isso, aspas fecham o atributo');

secao('3. CSRF');

$formularios = 0;
$comToken = 0;
foreach (array_merge($views, glob($raiz . '/views/*.php') ?: []) as $view) {
    $codigo = (string) file_get_contents($view);
    $formularios += substr_count($codigo, '<form method="post"');
    $comToken += substr_count($codigo, 'name="_token"');
}
checa('todo formulário POST tem _token', $formularios > 0 && $formularios === $comToken, "{$comToken}/{$formularios} formulários");

$rotas = (string) file_get_contents($raiz . '/routes/web.php');
$postsProtegidos = substr_count($rotas, 'CsrfMiddleware::class');
checa('e as rotas POST exigem o middleware', $postsProtegidos >= 3, "{$postsProtegidos} rotas");

secao('4. Cabeçalhos de segurança');

$cabecalhos = SecurityHeaders::cabecalhos();
foreach (['X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy', 'Content-Security-Policy'] as $h) {
    checa("envia {$h}", isset($cabecalhos[$h]), $cabecalhos[$h] ?? '');
}
checa('CSP começa restritiva', str_contains($cabecalhos['Content-Security-Policy'], "default-src 'self'"), 'e abre exceção pontual');
checa('HSTS NÃO em ambiente local', !isset($cabecalhos['Strict-Transport-Security']), 'é pegajoso: só com HTTPS estável');

secao('5. Rate limit em rotas sensíveis');

$api = (string) file_get_contents($raiz . '/routes/api.php');
checa('login web tem limite', str_contains(php_strip_whitespace($raiz . '/src/Auth/LoginPainel.php'), 'atingiu'), '');
checa('login da API também', str_contains(php_strip_whitespace($raiz . '/src/Http/Api/V1/AuthApiController.php'), 'rateLimiter'), '');
checa('PENDENTE: rate limit no webhook', !str_contains($api, 'rate_limit_webhook'), 'anotado em docs/hardening.md');

secao('6. Dependências');

$composer = json_decode((string) file_get_contents($raiz . '/composer.json'), true);
checa('composer audit está no quality', in_array('@security', $composer['scripts']['quality'], true), 'roda antes de todo deploy');

$saida = (string) shell_exec('cd ' . escapeshellarg($raiz) . ' && composer audit 2>&1');
checa('nenhuma vulnerabilidade conhecida', str_contains($saida, 'No security vulnerability'), trim($saida));

secao('7. Segredo fora do código');

$hardcoded = [];
foreach ($fontes as $arquivo => $codigo) {
    if (preg_match('/(password|senha|secret|api_key)\s*=\s*[\'"][^\'"]{8,}[\'"]/i', $codigo) === 1) {
        $hardcoded[] = basename($arquivo);
    }
}
checa('nenhum segredo hardcoded em src/', $hardcoded === [], $hardcoded === [] ? '' : implode(', ', $hardcoded));

secao('8. Senha e token guardados corretamente');

checa('senha com password_hash', str_contains(php_strip_whitespace($raiz . '/src/Domain/Usuario/Usuario.php'), 'password_hash'), '');
checa('token com sha256, nunca em claro', str_contains(php_strip_whitespace($raiz . '/src/Auth/TokenService.php'), "hash('sha256'"), '');

secao('9. Índices nas queries mais usadas');

$sqls = implode(' ', array_map(static fn (string $f): string => (string) file_get_contents($f), glob($raiz . '/migrations/*.up.sql') ?: []));
foreach (['idx_clientes_ativo' => 'listagem e busca', 'idx_jobs_status_disponivel' => 'polling do worker', 'idx_tentativas_chave_data' => 'rate limit'] as $indice => $para) {
    checa("índice {$indice}", str_contains($sqls, $indice), $para);
}

secao('Checklist versionado');

checa('docs/hardening.md existe', is_file($raiz . '/docs/hardening.md'), 'com as pendências declaradas');

fecharAula();
