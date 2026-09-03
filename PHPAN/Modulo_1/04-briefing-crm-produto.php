<?php

// PHPAN · Módulo 1 · Aula 04 — Briefing do CRM de produto
// metadados em aulas.json · a ideia em 04-briefing-crm-produto.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Usuario\Papel;

$raiz = __DIR__ . '/../crm-produto';

titulo('Aula 4 — Briefing do CRM de produto');

secao('Um projeto só, que evolui módulo a módulo');

nota('Em vez de 8 exercícios desconectados, um produto construído em fatias —');
nota('como acontece no mercado. Ninguém entrega um sistema inteiro de uma vez.');

secao('O mapa: briefing -> currículo -> o que existe');

$mapa = [
    ['Domínio', 'Cliente, Contato, Atividade + papéis', 'M2', $raiz . '/src/Domain/Cliente/Cliente.php'],
    ['Web (MVC)', 'painel com listar/criar, validação, CSRF', 'M3', $raiz . '/src/Http/Router.php'],
    ['API', '/api/v1 JSON com CRUD de clientes', 'M4', $raiz . '/src/Http/Api/V1/ClienteApiController.php'],
    ['Auth', 'sessão no painel + token na API, papéis', 'M5', $raiz . '/src/Auth/TokenService.php'],
    ['Produto', 'upload, fila, logs, soft delete, busca, CSV', 'M6', $raiz . '/src/Filas/Worker.php'],
    ['Produção', '.env, migrações, health check', 'M7', $raiz . '/bin/migrate.php'],
    ['Monetização', 'plano/limite + checkout/webhook', 'M8', $raiz . '/src/Billing/PlanLimiter.php'],
];

printf("  %-14s %-42s %-5s %s\n", 'ÁREA', 'RESUMO', 'ONDE', 'ENTREGUE?');
foreach ($mapa as [$area, $resumo, $modulo, $arquivo]) {
    printf("  %-14s %-42s %-5s %s\n", $area, $resumo, $modulo, is_file($arquivo) ? 'sim' : 'NÃO');
    checa("{$area} implementado", is_file($arquivo), basename($arquivo));
}

secao('FORA DE ESCOPO — explícito no briefing');

foreach (['Laravel/Symfony', 'multi-tenant completo', 'billing de produção maduro', 'cache distribuído', 'DDD pesado'] as $item) {
    echo "  · {$item}\n";
}

$temFramework = is_dir($raiz . '/vendor/laravel') || is_dir($raiz . '/vendor/symfony/framework-bundle');
checa('nenhum framework foi instalado', !$temFramework, 'resistiu à tentação');
nota('Se bater vontade de "fazer certo com framework", resista: é desvio de');
nota('objetivo pedagógico, não upgrade. Isso é PHPPRO.');

secao('A rubrica de entrega — critério de pronto do curso inteiro');

$rubrica = [
    'MVC com rotas e middleware' => is_file($raiz . '/src/Http/Kernel.php'),
    'API v1 documentada' => is_file($raiz . '/docs/api.md'),
    'Auth web + API + 2 papéis' => count(Papel::cases()) >= 2 && is_file($raiz . '/src/Auth/Sessao.php'),
    'Upload + fila de e-mail + logs' => is_file($raiz . '/src/Uploads/UploadService.php') && is_file($raiz . '/src/Log/Logger.php'),
    '.env + migração + deploy' => is_file($raiz . '/.env.example') && count(glob($raiz . '/migrations/*.up.sql') ?: []) > 0,
    'README do produto' => is_file($raiz . '/README.md'),
];
foreach ($rubrica as $item => $feito) {
    checa($item, $feito, '');
}
nota('Essa lista NÃO muda ao longo do curso — só vai sendo preenchida.');

secao('Papéis modelados cedo, fiscalização só no Módulo 5');

checa('enum Papel existe desde o domínio', count(Papel::cases()) === 3, implode(', ', array_map(static fn (Papel $p): string => $p->value, Papel::cases())));
checa('com comportamento simples', Papel::ADMIN->podeEditar() && !Papel::LEITURA->podeEditar(), 'podeEditar()');
nota('Modelar o CONCEITO de papel é diferente de implementar autorização.');
nota('Desenhar cedo evitou retrabalho estrutural lá no Módulo 5.');

secao('A entrega escrita da aula');

// Normaliza quebras de linha antes de procurar: markdown quebra frases no meio,
// e um str_contains ingênuo não acha "perde histórico" separado em duas linhas.
// (Já me pegou três vezes neste projeto.)
$readme = (string) preg_replace('/\s+/', ' ', (string) file_get_contents($raiz . '/README.md'));

checa('README diz quem é o usuário', str_contains($readme, 'Vendedor autônomo'), '');
checa('e qual dor ele resolve', str_contains($readme, 'perde histórico de contato'), 'pedido explícito do briefing');

echo "\n  Quem usa: vendedor autônomo ou equipe pequena que controla clientes em planilha\n";
echo "  Dor:      perde histórico de contato, esquece follow-up, não sabe o que foi\n";
echo "            combinado com quem\n";

secao('As gambiarras do Mini CRM, e onde foram resolvidas');

$backlog = [
    'SQL montado por interpolação na página' => 'M2/M3: repositório + prepared statement',
    'saída sem escape (XSS)' => 'M3: View::e() obrigatório',
    'sem validação; regra espalhada' => 'M2: invariante na entidade + M3: Validator',
];
foreach ($backlog as $gambiarra => $solucao) {
    printf("  %-44s %s\n", $gambiarra, $solucao);
}

secao('O projeto é a evolução, não uma cópia');

checa('mini-crm do PHPIAN intocado', is_dir(__DIR__ . '/../../PHPIAN/Modulo_8(modeagem_final)/mini-crm'), 'registro do antes');
checa('crm-produto é o depois', is_dir($raiz . '/src/Domain'), 'em camadas');

fecharAula();
