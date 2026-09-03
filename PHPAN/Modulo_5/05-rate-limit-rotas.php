<?php

// PHPAN · Módulo 5 · Aula 05 — Rate limit em rotas sensíveis
// metadados em aulas.json · a ideia em 05-rate-limit-rotas.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Auth\LoginPainel;
use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;
use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;
use App\Support\RateLimiter;

$pdo = bancoDaAula();
$limiter = new RateLimiter($pdo);

Container::repositorioDeUsuarios()->salvar(
    Usuario::novo('Ana Admin', 'admin@exemplo.com', 'senha-de-estudo', Papel::ADMIN),
);

titulo('Aula 5 — Rate limit em rotas sensíveis');

secao('O objetivo NÃO é impedir toda tentativa');

nota('É tornar força bruta lenta demais para valer a pena. Um script que testaria');
nota('milhares de senhas por minuto passa a conseguir 5 a cada 15 minutos.');

secao('Janela fixa: conta e bloqueia');

$chave = 'login:admin@exemplo.com:203.0.113.4';

for ($i = 1; $i <= LoginPainel::LIMITE_TENTATIVAS; $i++) {
    $bloqueou = $limiter->atingiu($chave, LoginPainel::LIMITE_TENTATIVAS, LoginPainel::JANELA_SEGUNDOS);
    checa("tentativa {$i} de " . LoginPainel::LIMITE_TENTATIVAS . ' passa', !$bloqueou, '');
}

checa(
    'a 6ª tentativa é bloqueada',
    $limiter->atingiu($chave, LoginPainel::LIMITE_TENTATIVAS, LoginPainel::JANELA_SEGUNDOS),
    'limite de ' . LoginPainel::LIMITE_TENTATIVAS . ' em ' . (LoginPainel::JANELA_SEGUNDOS / 60) . ' min',
);

secao('POR QUE a chave combina e-mail + IP');

checa(
    'outro e-mail no MESMO IP continua livre',
    !$limiter->atingiu('login:bruno@exemplo.com:203.0.113.4', 5, 900),
    'só IP puniria o escritório inteiro atrás do mesmo NAT',
);
checa(
    'o mesmo e-mail de OUTRO IP continua livre',
    !$limiter->atingiu('login:admin@exemplo.com:198.51.100.7', 5, 900),
    'só e-mail deixaria o atacante rodar em paralelo',
);
nota('Combinar os dois é o equilíbrio prático: nem pune inocente, nem deixa passar.');

secao('A janela vira sozinha');

$pdo->exec('UPDATE tentativas_login SET criado_em = DATE_SUB(NOW(), INTERVAL 20 MINUTE)');
checa(
    'tentativa velha sai da contagem',
    !$limiter->atingiu($chave, LoginPainel::LIMITE_TENTATIVAS, LoginPainel::JANELA_SEGUNDOS),
    'a janela é dos últimos 15 min, não do total histórico',
);

secao('Na rota de login da API');

$pdo->exec('DELETE FROM tentativas_login');
$router = Kernel::router();

$tentar = static fn (): App\Http\Response => $router->resolver(
    Request::falsa('POST', '/api/v1/auth/login', ['email' => 'admin@exemplo.com', 'senha' => 'chute'], [], ['REMOTE_ADDR' => '203.0.113.9']),
);

$status = [];
for ($i = 0; $i < 6; $i++) {
    $status[] = $tentar()->status;
}

checa('as 5 primeiras respondem 401 (credencial inválida)', array_slice($status, 0, 5) === [401, 401, 401, 401, 401], implode(' ', $status));

$bloqueada = $tentar();
checa('depois vem 429 rate_limited', $bloqueada->status === 429, 'HTTP 429');
checa(
    'com cabeçalho Retry-After',
    isset($bloqueada->headers['Retry-After']),
    'Retry-After: ' . ($bloqueada->headers['Retry-After'] ?? 'AUSENTE'),
);
nota('Sem Retry-After o cliente tenta de novo imediatamente e piora o problema.');

secao('Limpeza: senão a tabela cresce para sempre');

$pdo->exec('UPDATE tentativas_login SET criado_em = DATE_SUB(NOW(), INTERVAL 2 DAY)');
$removidos = $limiter->limparAntigos(86400);

checa('limparAntigos() remove registro velho', $removidos > 0, "{$removidos} removido(s)");
nota('Isso roda em cron. Sem ele, a tabela de tentativas nunca para de crescer.');

secao('Onde MAIS aplicar (além do login)');

$rotas = [
    'POST /auth/login'       => 'força bruta de senha',
    'POST /esqueci-senha'    => 'spam de e-mail e enumeração de usuários',
    'POST /webhooks/*'       => 'abuso de endpoint público (mesmo com assinatura)',
    'GET  /exportar'         => 'endpoint caro: cada chamada varre a base',
];
foreach ($rotas as $rota => $motivo) {
    printf("  %-24s %s\n", $rota, $motivo);
}
nota('Rate limit NÃO substitui autorização (aula 3): são camadas diferentes.');

fecharAula();
