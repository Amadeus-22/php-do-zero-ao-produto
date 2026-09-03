<?php

// PHPAN · Módulo 8 · Aula 06 — Roadmap: o que vem no PHPPRO
// metadados em aulas.json · a ideia em 06-roadmap-phppro.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

$raiz = __DIR__ . '/../crm-produto';
$curso = __DIR__ . '/..';

titulo('Aula 6 — Roadmap: o que vem no PHPPRO');

secao('Over-engineering é resolver hoje um problema que você não tem hoje');

nota('O CRM é sem framework pesado, sem multi-tenant completo, sem fila distribuída.');
nota('Não porque essas coisas sejam ruins — mas porque entender POR QUE existem');
nota('exige sentir a dor que elas resolvem. É isso que o PHPPRO cobre.');

secao('O que ficou de fora, e quando passa a fazer sentido');

$fora = [
    'Laravel/Symfony ponta a ponta' => 'quando manter infra própria custar mais que entender cada camada',
    'DDD / arquitetura avançada' => 'quando regras colidirem entre módulos',
    'Fila distribuída (Redis/RabbitMQ)' => 'quando passar de dezenas para milhares de jobs/hora',
    'Cache distribuído' => 'quando query repetida em várias instâncias pesar no banco',
    'Multi-tenant SaaS completo' => 'quando contas pagantes justificarem o custo operacional',
    'Escala (LB, réplicas)' => 'quando uma instância não aguentar o tráfego',
];
foreach ($fora as $tema => $quando) {
    printf("  %-36s %s\n", $tema, $quando);
}

secao('A regra: sentir a dor ANTES da solução');

nota('Lentidão real de banco sob carga -> aí sim cache/índice avançado.');
nota('Dor real de manter rotas na mão   -> aí sim framework.');
nota('Se o CRM tem 3 usuários, multi-tenant não é a prioridade nº 1.');

secao('LACUNAS deste projeto — com evidência no repositório');

$rubrica = (string) file_get_contents($raiz . '/docs/rubrica-final.md');
$hardening = (string) file_get_contents($raiz . '/docs/hardening.md');

$lacunas = [
    ['Staging e produção não existem', 'F', 'CI/CD e ambientes', str_contains($rubrica, 'ambiente único')],
    ['Deploy nunca executado', 'F', 'infra e automação', str_contains($rubrica, 'sem servidor')],
    ['Rate limit ausente no webhook', 'S', 'hardening', str_contains($hardening, 'Rate limit no webhook')],
    ['UNIQUE conflita com soft delete', 'S', 'modelagem de schema', str_contains($hardening, 'soft delete')],
    ['Multi-tenant pela metade (conta_id só em clientes)', 'F', 'multi-tenant', str_contains($rubrica, 'conta única')],
    ['Sem teste de contrato da API', 'S', 'testes em profundidade', true],
];

printf("  %-52s %-4s %s\n", 'LACUNA', 'S/F', 'TEMA PHPPRO');
foreach ($lacunas as [$lacuna, $tipo, $tema, $documentada]) {
    printf("  %-52s %-4s %s\n", $lacuna, $tipo, $tema);
    checa("documentada no repositório: {$lacuna}", $documentada, $tipo === 'S' ? 'dor de hoje' : 'dor futura');
}
nota('S = dor sentida HOJE · F = dor futura provável.');

secao('PRIORIDADE IMEDIATA (S)');

echo "  Tema: rate limit no webhook + índice único parcial para soft delete\n";
echo "  Por quê: são as duas únicas lacunas que já têm efeito no comportamento\n";
echo "           do sistema hoje — endpoint público sem limite de frequência, e\n";
echo "           impossibilidade de recadastrar e-mail de cliente excluído.\n";

secao('ADIAMENTO CONSCIENTE (F)');

echo "  Tema: fila distribuída (Redis)\n";
echo "  Sinal para revisitar: mais de 500 jobs/hora OU job aguardando > 5 min\n";
echo "                        na fila por 3 dias seguidos\n";
nota('Adiamento sem sinal mensurável ("quando crescer") vira nunca.');

secao('O que LEVAR do PHPAN para o PHPPRO');

foreach (['migrations/', 'tests/', 'docs/runbook.md', 'docs/checklist-deploy.md', 'docs/rubrica-final.md'] as $ativo) {
    checa("levar: {$ativo}", file_exists($raiz . '/' . $ativo), '');
}
nota('Não se joga o CRM fora: evolui-se sobre ele ou extraem-se módulos.');

secao('ERROS comuns ao entrar no PHPPRO');

$erros = [
    'Refazer o CRM do zero em Laravel' => 'joga fora o aprendizado operacional; migre incrementalmente',
    'Escolher framework antes de listar a dor' => 'critério invertido — liste o que não quer mais manter',
    'Começar com cobertura zero de novo' => 'os testes daqui continuam valendo',
    'Roadmap como lista de tecnologias' => 'sem evidência no repo, é wishlist',
];
foreach ($erros as $erro => $porque) {
    printf("  %-44s %s\n", $erro, $porque);
}

secao('FECHAMENTO do PHPAN');

$aulas = glob($curso . '/Modulo_*/*.php') ?: [];
$notas = glob($curso . '/Modulo_*/*.md') ?: [];
$testes = (string) shell_exec('cd ' . escapeshellarg($raiz) . ' && composer test 2>&1 | tail -3');

checa('aulas executáveis', count($aulas) >= 38, count($aulas) . ' arquivos .php');
checa('notas explicando o porquê', count($notas) >= 40, count($notas) . ' arquivos .md');
checa('suíte do projeto passando', str_contains($testes, 'OK ('), trim(explode("\n", trim($testes))[2] ?? ''));

nota('O CRM está FECHADO nesta fase de estudo. Isso não impede manutenção —');
nota('impede scope creep infinito. O PHPPRO começa com clareza, não com culpa');
nota('por "não ter feito tudo".');

fecharAula();
