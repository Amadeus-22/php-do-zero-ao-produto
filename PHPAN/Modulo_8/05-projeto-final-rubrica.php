<?php

// PHPAN · Módulo 8 · Aula 05 — Projeto final: rubrica de entrega
// metadados em aulas.json · a ideia em 05-projeto-final-rubrica.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

$raiz = __DIR__ . '/../crm-produto';
$curso = __DIR__ . '/..';

titulo('Aula 5 — Rubrica de entrega');

secao('Rubrica não é burocracia');

nota('É o que separa "projeto de curso" de "produto". Ninguém acredita em');
nota('"funciona" sem evidência: comando rodado, teste passando, ou o sistema no ar.');

secao('DOMÍNIO');

foreach (['Cliente', 'Contato', 'Atividade'] as $entidade) {
    checa("entidade {$entidade}", is_file("{$raiz}/src/Domain/{$entidade}/{$entidade}.php"), '');
}
checa('schema versionado em .sql', count(glob($raiz . '/migrations/*.up.sql') ?: []) >= 15, count(glob($raiz . '/migrations/*.up.sql') ?: []) . ' migrações');

secao('WEB (MVC)');

checa('painel de clientes', is_file($raiz . '/views/clientes/index.php'), '');
checa('validação com erro por campo', is_file($raiz . '/src/Validation/ClienteValidator.php'), '');
checa('CSRF em formulário de escrita', str_contains((string) file_get_contents($raiz . '/views/clientes/novo.php'), 'name="_token"'), '');

secao('API');

$doc = (string) file_get_contents($raiz . '/docs/api.md');
foreach (['GET /clientes', 'POST /clientes', 'PUT /clientes/{id}', 'DELETE /clientes/{id}'] as $rota) {
    checa("documentado: {$rota}", str_contains($doc, $rota), '');
}
checa('paginação com meta', str_contains($doc, '"total_pages"'), '');
checa('erro padronizado', str_contains($doc, 'validation_failed'), '');

secao('AUTH');

checa('sessão no painel', is_file($raiz . '/src/Auth/Sessao.php'), '');
checa('token na API', is_file($raiz . '/src/Auth/TokenService.php'), '');
checa('três papéis com permissões diferentes', count(App\Domain\Usuario\Papel::cases()) === 3, 'admin, vendedor, leitura');

secao('PRODUTO');

$entregas = [
    'upload validado' => $raiz . '/src/Uploads/UploadService.php',
    'fila de jobs' => $raiz . '/src/Filas/Worker.php',
    'logs estruturados' => $raiz . '/src/Log/Logger.php',
    'lembretes' => $raiz . '/src/Application/Lembrete/LembreteService.php',
    'exportação CSV' => $raiz . '/src/Exportacao/ExportadorDeClientesCsv.php',
];
foreach ($entregas as $rotulo => $arquivo) {
    checa($rotulo, is_file($arquivo), '');
}
checa('soft delete + lixeira', str_contains((string) file_get_contents($raiz . '/src/Domain/Cliente/RepositorioDeClientes.php'), 'removidos'), '');

secao('PRODUÇÃO');

checa('.env e .env.example', is_file($raiz . '/.env') && is_file($raiz . '/.env.example'), '');
checa('migrações com up e down', count(glob($raiz . '/migrations/*.down.sql') ?: []) === count(glob($raiz . '/migrations/*.up.sql') ?: []), '');
checa('backup + restauração testados', is_file($raiz . '/scripts/restaurar-db.sh'), 'RTO medido no Módulo 7');
checa('/health com dependências', is_file($raiz . '/src/HealthCheck.php'), '');
checa('runbook de incidente', is_file($raiz . '/docs/runbook.md'), '');

secao('MONETIZAÇÃO');

checa('planos com limite', is_file($raiz . '/src/Billing/PlanLimiter.php'), '');
checa('webhook com HMAC e idempotência', is_file($raiz . '/src/Billing/WebhookPagamento.php'), '');

secao('DOCUMENTAÇÃO');

foreach (['README.md', 'docs/api.md', 'docs/runbook.md', 'docs/hardening.md', 'docs/rubrica-final.md'] as $arquivo) {
    checa($arquivo, is_file("{$raiz}/{$arquivo}"), '');
}
$readme = (string) file_get_contents($raiz . '/README.md');
checa('README diz quem usa e que dor resolve', str_contains($readme, 'dor resolve'), 'pedido do briefing (Módulo 1, aula 4)');
checa('e como instalar do zero', str_contains($readme, 'composer install') && str_contains($readme, 'migrate.php up'), '');

secao('PORTÃO DE QUALIDADE');

$saida = (string) shell_exec('cd ' . escapeshellarg($raiz) . ' && composer quality 2>&1');

checa('estilo sem pendência', str_contains($saida, 'Found 0 of'), '');
checa('PHPStan sem erro', str_contains($saida, '[OK] No errors'), 'level 5');
checa('testes passando', (bool) preg_match('/OK \((\d+) tests/', $saida, $m), ($m[1] ?? '?') . ' testes');
checa('sem vulnerabilidade em dependência', str_contains($saida, 'No security vulnerability'), 'composer audit');

secao('AULAS EXECUTÁVEIS');

$aulas = glob($curso . '/Modulo_*/*.php') ?: [];
$falhas = [];
foreach ($aulas as $aula) {
    if (basename($aula) === basename(__FILE__)) {
        continue; // não chama a si mesma
    }

    exec('php ' . escapeshellarg($aula) . ' > /dev/null 2>&1', $_, $codigo);

    if ($codigo !== 0) {
        $falhas[] = basename(dirname($aula)) . '/' . basename($aula);
    }
}
checa('todas as aulas rodam sem falha', $falhas === [], count($aulas) . ' aulas' . ($falhas === [] ? '' : ' — falhou: ' . implode(', ', $falhas)));

secao('PENDÊNCIAS — declaradas, não escondidas');

$rubrica = (string) file_get_contents($raiz . '/docs/rubrica-final.md');
foreach (['Staging e produção', 'Deploy + HTTPS', 'Rate limit no webhook', 'UNIQUE` + soft delete'] as $pendencia) {
    checa("declarada: {$pendencia}", str_contains($rubrica, $pendencia), 'com motivo e prazo');
}
nota('Marcar item como feito sem evidência é exatamente o que a rubrica existe');
nota('para impedir. O que não foi feito está escrito como não feito.');

fecharAula();
