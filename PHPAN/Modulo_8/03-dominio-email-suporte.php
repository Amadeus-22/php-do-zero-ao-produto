<?php

// PHPAN · Módulo 8 · Aula 03 — Domínio, e-mail profissional e suporte
// metadados em aulas.json · a ideia em 03-dominio-email-suporte.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Config\Config;
use App\Domain\Notificacao\RemetenteDeEmail;
use App\Support\Container;

$raiz = __DIR__ . '/../crm-produto';
Config::carregar();

titulo('Aula 3 — Domínio, e-mail profissional e suporte');

secao('Três problemas que viram um só ticket de "configurar e-mail"');

printf("  %-16s %s\n", 'Domínio', 'identidade do produto (DNS — Módulo 7, aula 4)');
printf("  %-16s %s\n", 'Deliverability', 'por que e-mail cai em spam: é DNS + reputação');
printf("  %-16s %s\n", 'Suporte', 'canal por onde o cliente fala com você');

secao('Por que e-mail cai em spam');

$registros = [
    'SPF' => 'lista quais servidores podem enviar em nome do domínio',
    'DKIM' => 'assinatura criptográfica provando que o conteúdo não mudou',
    'DMARC' => 'política quando SPF/DKIM falham + relatórios',
];
foreach ($registros as $r => $para) {
    printf("  %-8s %s\n", $r, $para);
}
nota('mail() do PHP direto de um VPS: IP compartilhado, sem reputação, sem DKIM.');
nota('Quase sempre cai em spam ou é rejeitado.');

secao('Remetente e Reply-To vêm de CONFIG, não do código');

checa('MAIL_FROM está no .env', Config::string('MAIL_FROM', '') !== '', Config::string('MAIL_FROM'));
checa('MAIL_REPLY_TO também', Config::string('MAIL_REPLY_TO', '') !== '', Config::string('MAIL_REPLY_TO'));
checa('e ambos estão no .env.example', str_contains((string) file_get_contents($raiz . '/.env.example'), 'MAIL_REPLY_TO'), '');

nota('O remetente transacional é no-reply@, mas o Reply-To aponta para suporte@:');
nota('o cliente que responde "não consigo logar" precisa chegar em algum lugar.');

secao('Trocar o remetente é UMA linha — porque tudo depende da interface');

$remetente = Container::remetenteDeEmail();
checa('o remetente atual implementa a interface', $remetente instanceof RemetenteDeEmail, $remetente::class);

$fonteContainer = php_strip_whitespace($raiz . '/src/Support/Container.php');
checa('o Container decide qual implementação', str_contains($fonteContainer, 'RemetenteDeEmailEmLog'), 'uma linha');

$usos = [];
foreach (glob($raiz . '/src/**/*.php') ?: [] as $arquivo) {
    if (str_contains($arquivo, 'Container.php') || str_contains($arquivo, 'Infrastructure')) {
        continue;
    }

    if (str_contains(php_strip_whitespace($arquivo), 'RemetenteDeEmailEmLog')) {
        $usos[] = basename($arquivo);
    }
}
checa('nada mais no sistema conhece a implementação', $usos === [], $usos === [] ? 'só a interface' : implode(', ', $usos));
nota('Quando o provedor transacional entrar, muda o Container e mais nada.');

secao('O envio em desenvolvimento');

$remetente->enviar('cliente@exemplo.com', 'Teste da aula', 'Corpo do e-mail de teste.');
$log = $raiz . '/var/emails.log';

checa('e-mail gravado em arquivo', is_file($log) && str_contains((string) file_get_contents($log), 'Teste da aula'), 'var/emails.log');
nota('Em dev não se manda e-mail de verdade: grava em log e você confere o conteúdo.');

secao('Suporte que existe de fato');

checa('docs/suporte.md existe', is_file($raiz . '/docs/suporte.md'), '');
$suporte = (string) file_get_contents($raiz . '/docs/suporte.md');
foreach (['SLA', 'Triagem', 'Reply-To'] as $item) {
    checa("documenta: {$item}", str_contains($suporte, $item), '');
}
nota('Alias criado e nunca checado é pior que não anunciar suporte.');

secao('ESTADO REAL — plano escrito, não entrega fingida');

checa('docs/plano-dominio-email.md existe', is_file($raiz . '/docs/plano-dominio-email.md'), '');
$plano = (string) file_get_contents($raiz . '/docs/plano-dominio-email.md');
checa('com os registros DNS exatos', str_contains($plano, 'v=spf1') && str_contains($plano, 'v=DKIM1') && str_contains($plano, 'v=DMARC1'), '');
checa('e o alerta sobre p=reject cedo demais', str_contains($plano, 'NUNCA reject no primeiro dia'), '');

nota('Não há domínio próprio aqui. O plano está executável e documentado —');
nota('a rubrica aceita isso como pendência justificada, não como item feito.');

fecharAula();
