<?php

// PHPAN · Módulo 4 · Aula 06 — Documentar endpoints (mínimo legível para humano)
// metadados em aulas.json · a ideia em 06-documentar-endpoints.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

titulo('Aula 6 — Documentar endpoints');

$projeto = __DIR__ . '/../crm-produto';
$doc = (string) file_get_contents($projeto . '/docs/api.md');
$rotasFonte = (string) file_get_contents($projeto . '/routes/api.php');

secao('O documento existe e está no lugar previsto');

checa('docs/api.md existe', $doc !== '', strlen($doc) . ' bytes');
checa('declara a Base URL', str_contains($doc, 'Base URL: `/api/v1`'), '');
checa('declara a autenticação exigida', stripos($doc, 'autentica') !== false, '');
checa('fixa UM formato de data para o projeto', str_contains($doc, 'DATE_ATOM'), 'nada de Y-m-d aqui e timestamp ali');

secao('Toda rota registrada está documentada');

preg_match_all("#\\\$router->(\w+)\('(/api/v1[^']*)'#", $rotasFonte, $m, PREG_SET_ORDER);
checa('rotas encontradas em routes/api.php', count($m) >= 5, count($m) . ' rotas (o número cresce a cada módulo)');

foreach ($m as [, $metodo, $caminho]) {
    $secao = strtoupper($metodo) . ' ' . str_replace('/api/v1', '', $caminho);
    checa("documentada: {$secao}", str_contains($doc, $secao), '');
}

secao('Não documentar só o caminho feliz');

foreach (['422', '404', '409', 'validation_failed', 'not_found', 'conflict'] as $erro) {
    checa("erro {$erro} aparece na doc", str_contains($doc, $erro), '');
}
nota('Quem lê só o 200/201 descobre o 422 quebrando em produção.');

secao('Exemplos com dado FICTÍCIO');

checa('usa domínio de exemplo, não e-mail real', str_contains($doc, '@exemplo.com'), '');
checa('traz exemplos executáveis com curl', str_contains($doc, 'curl -s'), '');

secao('A regra que impede a doc de envelhecer');

checa(
    'a doc declara a regra de manutenção junto ao commit',
    stripos($doc, 'o mesmo commit atualiza') !== false,
    '',
);
checa(
    'existe teste automatizado guardando isso',
    is_file($projeto . '/tests/DocumentacaoApiTest.php'),
    'DocumentacaoApiTest falha se surgir rota não documentada',
);
nota('Foi o jeito de tornar "documentar" um hábito verificável, não uma promessa:');
nota('adicione uma rota nova em routes/api.php sem documentar e composer test falha.');

secao('Próximo passo natural (fora do escopo agora)');

nota('OpenAPI/Swagger gera doc interativa a partir de anotações no código.');
nota('O objetivo desta fase é o HÁBITO de documentar, não a ferramenta.');

fecharAula();
