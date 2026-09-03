<?php

// PHPAN · Módulo 8 · Aula 01 — Planos, limites e "access granted"
// metadados em aulas.json · a ideia em 01-planos-limites.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Billing\AssinaturaService;
use App\Billing\PlanLimiter;
use App\Domain\Usuario\Gate;
use App\Domain\Usuario\Papel;
use App\Support\Container;

$pdo = bancoDaAula();
foreach (['eventos_webhook', 'assinaturas', 'planos'] as $t) {
    $pdo->exec("DELETE FROM {$t}");
}

$pdo->exec("INSERT INTO planos (codigo, nome, max_clientes, max_usuarios) VALUES ('free', 'Free', 2, 1), ('pro', 'Pro', 100, 10)");
$free = (int) $pdo->query("SELECT id FROM planos WHERE codigo = 'free'")->fetchColumn();
$pdo->exec("INSERT INTO assinaturas (conta_id, plano_id, status, renova_em) VALUES (1, {$free}, 'ativa', DATE_ADD(CURDATE(), INTERVAL 1 MONTH))");
$assinaturaId = (int) $pdo->lastInsertId();

$limiter = new PlanLimiter($pdo);
$assinaturas = new AssinaturaService($pdo);

titulo('Aula 1 — Planos, limites e "access granted"');

secao('Assinatura, reduzida ao essencial');

nota('"Este cliente pagante tem direito a X até quando?"');
printf("  %-14s %s\n", 'Plano', 'conjunto nomeado de limites (free, pro)');
printf("  %-14s %s\n", 'Assinatura', 'conta + plano + status + validade');
printf("  %-14s %s\n", 'Access granted', 'a checagem em runtime — é ela que trava ou libera');

secao('O limite do plano free');

checa('limite de clientes do free é 2', $limiter->limiteDe(1, 'max_clientes') === 2, '');
checa('com 0 clientes, pode criar', $limiter->podeCriarCliente(1), '');

Container::clienteService()->criar(['nome' => 'Cliente A', 'email' => 'a@exemplo.com']);
checa('com 1 de 2, ainda pode', $limiter->podeCriarCliente(1), '');

Container::clienteService()->criar(['nome' => 'Cliente B', 'email' => 'b@exemplo.com']);
checa('no limite, BLOQUEIA', !$limiter->podeCriarCliente(1), '2 de 2');

secao('Sem assinatura ativa = ZERO acesso');

$pdo->exec("UPDATE assinaturas SET status = 'cancelada'");
checa('limite vira 0, não infinito', $limiter->limiteDe(1, 'max_clientes') === 0, '');
checa('e nada pode ser criado', !$limiter->podeCriarCliente(1), 'fail closed');
nota('O padrão seguro é negar. "Sem plano = sem limite" seria o contrário.');
$assinaturas->ativar($assinaturaId);

secao('Upgrade destrava sem tocar em código');

$pro = (int) $pdo->query("SELECT id FROM planos WHERE codigo = 'pro'")->fetchColumn();
$pdo->exec("UPDATE assinaturas SET plano_id = {$pro}");

checa('no plano pro o limite sobe', $limiter->limiteDe(1, 'max_clientes') === 100, '');
checa('e volta a poder criar', $limiter->podeCriarCliente(1), '');
$pdo->exec("UPDATE assinaturas SET plano_id = {$free}");

secao('GRACE PERIOD: atraso não trava na hora');

$assinaturas->marcarAtrasada($assinaturaId);
checa('atrasada hoje ainda escreve', $limiter->podeEscrever(1), 'tolerância de ' . PlanLimiter::GRACE_DIAS . ' dias');

$pdo->exec('UPDATE assinaturas SET atrasada_desde = DATE_SUB(CURDATE(), INTERVAL 10 DAY)');
checa('atrasada há 10 dias, bloqueia escrita', !$limiter->podeEscrever(1), '');
nota('Bloqueio abrupto gera cancelamento por atrito em vez de renovação.');
nota('A leitura continua liberada: o cliente vê os dados dele, só não escreve.');

$assinaturas->ativar($assinaturaId);

secao('O limite tem que valer nos DOIS pontos de entrada');

// VERIFICAÇÃO POR COMPORTAMENTO, não por leitura de código.
//
// A primeira versão desta aula conferia que o método existia na classe e que os
// controllers não hardcodavam número. Passava verde — com o limite DESLIGADO:
// o PlanLimiter estava pronto e nunca era chamado. Ler o código provou o que eu
// queria acreditar; só criar acima do teto prova o que o sistema faz.
// A conta já está no limite (2 de 2), criados na seção acima.
$service = Container::clienteService();

checaExcecao(
    'pelo SERVICE: criar acima do limite falha',
    App\Billing\LimiteDoPlanoAtingido::class,
    static fn () => $service->criar(['nome' => 'Cliente 3', 'email' => 'c3@exemplo.com']),
);

$token = tokenDeAula();
$respostaApi = App\Http\Kernel::router()->resolver(
    App\Http\Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Via API', 'email' => 'api@exemplo.com']),
);

checa('pela API: mesmo limite, HTTP 403', $respostaApi->status === 403, 'não 201');
checa(
    'com código próprio para o front tratar',
    json_decode($respostaApi->body, true)['error']['code'] === 'plan_limit_reached',
    'diferente de forbidden (papel) e de conflict (e-mail)',
);
nota('Checar só no formulário web e esquecer a API significa que o limite NÃO');
nota('existe — quem descobrir o endpoint o contorna inteiro.');

secao('SQL: o nome da coluna NÃO pode vir de fora');

checaExcecao(
    'coluna arbitrária é recusada',
    InvalidArgumentException::class,
    static fn () => $limiter->limiteDe(1, 'max_clientes; DROP TABLE planos'),
);
nota('Nome de coluna não dá para passar como parâmetro preparado — por isso');
nota('a whitelist. É o único lugar onde algo entra concatenado na query.');

secao('PAPEL x PLANO: conceitos diferentes');

$gate = new Gate();
printf("  %-10s %-46s %s\n", 'Papel', 'o que o USUÁRIO pode fazer na conta', 'vendedor cria: ' . ($gate->pode(Papel::VENDEDOR, 'cliente.criar') ? 'sim' : 'não'));
printf("  %-10s %-46s %s\n", 'Plano', 'quanto a CONTA pode ter/usar', 'limite: ' . $limiter->limiteDe(1, 'max_clientes') . ' clientes');

nota('Um vendedor (papel que pode criar) numa conta free que atingiu o limite');
nota('continua barrado — pelo plano, não pelo papel. As duas checagens coexistem.');

fecharAula();
