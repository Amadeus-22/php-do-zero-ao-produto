<?php

// PHPAN · Módulo 7 · Aula 03 — Staging vs produção
// metadados em aulas.json · a ideia em 03-staging-vs-producao.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Config\Config;

$raiz = __DIR__ . '/../crm-produto';
Config::carregar();

titulo('Aula 3 — Staging vs produção');

secao('Paridade é o que faz staging valer');

printf("  %-34s %s\n", 'MUDA entre ambientes', 'NÃO muda');
printf("  %-34s %s\n", 'valores do .env', 'código (mesmo commit/tag)');
printf("  %-34s %s\n", 'volume e origem do dado', 'versão de PHP e extensões');
printf("  %-34s %s\n", 'quem acessa (Basic Auth/IP)', 'estrutura de infra');
nota('Staging com PHP 8.1 contra produção 8.3 não é staging — é outro dev.');

secao('APP_ENV controla o que o usuário vê quando quebra');

$bootstrap = (string) file_get_contents($raiz . '/public/index.php');

checa('o boot lê APP_ENV', str_contains($bootstrap, "Config::string('APP_ENV'"), '');
checa('produção esconde o erro', str_contains($bootstrap, "ini_set('display_errors', '0')"), 'vai para o log');
checa('fora de produção mostra', str_contains($bootstrap, "ini_set('display_errors', '1')"), '');
nota('Stack trace na tela entrega caminho de arquivo, versão de biblioteca e');
nota('às vezes a string de conexão. APP_DEBUG é questão de segurança, não de UX.');

secao('Arquivos de ambiente separados');

$gitignore = (string) file_get_contents($raiz . '/.gitignore');

checa('.env.* ignorado (staging e production)', str_contains($gitignore, '.env.*'), '');
checa('.env.example versionado', str_contains($gitignore, '!.env.example'), 'documenta as chaves');
nota('Cada servidor tem o seu, fisicamente separado. Um .env compartilhado');
nota('entre staging e produção é o mesmo que não ter separação.');

secao('Banco separado é DEAL-BREAKER');

nota('Staging apontando para o banco de produção transforma qualquer teste em');
nota('mutação real. Não há configuração que torne isso aceitável.');

secao('Checklist de promoção — escrito, não decorado');

checa('docs/checklist-deploy.md existe', is_file($raiz . '/docs/checklist-deploy.md'), '');

$checklist = (string) file_get_contents($raiz . '/docs/checklist-deploy.md');
foreach (['Backup de produção', 'migrate.php status', 'rollback', 'Smoke test', 'health'] as $item) {
    checa("checklist cobre: {$item}", stripos($checklist, $item) !== false, '');
}
nota('Se não está escrito, não é repetível — deploy às 23h, cansado, pula passo.');

secao('Dado de staging: fake ou anonimizado, nunca produção direta');

$staging = (string) file_get_contents($raiz . '/docs/staging.md');
checa('a política está documentada', str_contains($staging, 'anonimiz'), 'docs/staging.md');
checa('com exemplo de UPDATE de anonimização', str_contains($staging, 'example.test'), '');
nota('Dado real de cliente em staging é risco de LGPD e vazamento acidental.');

secao('ESTADO REAL deste projeto');

$ambiente = Config::string('APP_ENV', 'production');
echo "  APP_ENV atual: {$ambiente}\n";

checa('ambiente único (local, Docker)', $ambiente === 'local', 'staging e produção ainda NÃO existem');
nota('Isto está declarado em docs/staging.md em vez de fingir que foi feito.');
nota('A paridade a garantir quando existirem: PHP 8.3+, MySQL 8, mesmo commit.');

secao('Promover TAG, não "o que estiver na main"');

nota('git describe em produção tem que bater com o que foi testado em staging.');
nota('Senão o teste em staging não diz nada sobre o que está no ar.');

fecharAula();
