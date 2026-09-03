<?php

// PHPAN · Módulo 7 · Aula 04 — Deploy (VPS ou hospedagem avançada) + HTTPS
// metadados em aulas.json · a ideia em 04-deploy-https.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

$raiz = __DIR__ . '/../crm-produto';

titulo('Aula 4 — Deploy + HTTPS');

secao('Três decisões, iguais em VPS ou hospedagem');

printf("  %-28s %s\n", 'Document root', 'aponta só para public/');
printf("  %-28s %s\n", 'HTTPS', 'não é opcional — sessão e token viajam nele');
printf("  %-28s %s\n", 'Deploy', 'processo repetível, não scp manual');

secao('O QUE FICA EXPOSTO se o root apontar para a raiz do repositório');

$perigosos = ['.env', 'composer.json', 'composer.lock', 'phpstan.neon'];
foreach ($perigosos as $arquivo) {
    $existe = is_file($raiz . '/' . $arquivo);
    printf("  %-18s %s\n", $arquivo, $existe ? 'acessível por URL — segredo e stack expostos' : '(ausente)');
}

$emPublic = array_map('basename', glob($raiz . '/public/*') ?: []);
checa('public/ contém só o necessário', !in_array('.env', $emPublic, true), implode(', ', $emPublic));
checa('o front controller está lá', in_array('index.php', $emPublic, true), '');
checa('src/ NÃO está dentro de public/', !is_dir($raiz . '/public/src'), '');
checa('storage/ NÃO está dentro de public/', !is_dir($raiz . '/public/storage'), 'anexos fora do alcance');

secao('nginx.conf versionado');

$nginx = (string) file_get_contents($raiz . '/deploy/nginx.conf');

checa('root aponta para current/public', str_contains($nginx, 'current/public'), 'não para current/');
checa('bloqueia dotfiles', str_contains($nginx, 'location ~ /\\.(?!well-known)'), 'segunda barreira para .env e .git');
checa('deixa passar .well-known', str_contains($nginx, 'well-known'), 'senão o certbot não renova');

secao('Releases + symlink: por que esse padrão');

$deploy = (string) file_get_contents($raiz . '/deploy/deploy.sh');

checa('cria release datada', str_contains($deploy, 'releases/$RELEASE'), '');
checa('troca o symlink current', str_contains($deploy, 'ln -sfn "$DIR" "$BASE/current"'), 'operação atômica');
checa('mantém as 5 últimas', str_contains($deploy, 'tail -n +6'), 'é o que torna o rollback instantâneo');
checa('.env e storage são compartilhados', str_contains($deploy, 'shared/.env') && str_contains($deploy, 'shared/storage'), 'não se recriam a cada deploy');

secao('composer install --no-dev');

checa('deploy usa --no-dev', str_contains($deploy, '--no-dev'), '');
checa('e --optimize-autoloader', str_contains($deploy, '--optimize-autoloader'), '');
nota('PHPUnit, PHPStan e CS-Fixer não têm o que fazer no servidor de produção:');
nota('menos dependência = menos superfície de ataque e deploy mais rápido.');

secao('Recarregar o FPM — o esquecimento clássico');

checa('deploy recarrega o php-fpm', str_contains($deploy, 'systemctl reload php8.3-fpm'), '');
nota('Sem isso o OPcache continua servindo o código ANTIGO por horas, e você');
nota('fica olhando para um bug que já foi corrigido.');

secao('Smoke test dentro do próprio deploy');

checa('o script bate no /health', str_contains($deploy, '/health'), 'falhou -> considere rollback');

secao('HTTPS');

echo "  certbot --nginx -d crm.exemplo.com\n";
echo "  certbot renew --dry-run     # TESTAR a renovação, não confiar nela\n";
nota('Sem TLS, sessão de login e token de API trafegam em texto puro.');
nota('Let\'s Encrypt é grátis: não existe desculpa de custo.');

secao('Worker e cron também são deploy');

foreach (['crm-worker.service' => 'mantém o worker de pé', 'crontab.txt' => 'lembretes, backup, limpeza', 'logrotate.conf' => 'impede o disco de encher'] as $arquivo => $para) {
    checa("deploy/{$arquivo}", is_file($raiz . "/deploy/{$arquivo}"), $para);
}

secao('ESTADO REAL');

nota('Sem VPS nem domínio aqui: os arquivos estão versionados e revisáveis,');
nota('mas nenhum deploy foi executado. Está declarado, não simulado.');

fecharAula();
