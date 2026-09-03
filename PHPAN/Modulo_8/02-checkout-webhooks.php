<?php

// PHPAN · Módulo 8 · Aula 02 — Checkout conceitual, gateway e webhooks
// metadados em aulas.json · a ideia em 02-checkout-webhooks.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Billing\AssinaturaService;
use App\Billing\WebhookPagamento;
use App\Log\Logger;

$pdo = bancoDaAula();
foreach (['eventos_webhook', 'assinaturas', 'planos'] as $t) {
    $pdo->exec("DELETE FROM {$t}");
}
$pdo->exec("INSERT INTO planos (codigo, nome, max_clientes, max_usuarios) VALUES ('pro', 'Pro', 100, 10)");
$planoId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO assinaturas (conta_id, plano_id, status, renova_em) VALUES (1, {$planoId}, 'atrasada', CURDATE())");
$assinaturaId = (int) $pdo->lastInsertId();

$SEGREDO = 'segredo-de-estudo';
$webhook = new WebhookPagamento($pdo, new AssinaturaService($pdo), $SEGREDO, new Logger(sys_get_temp_dir() . '/aula-webhook.jsonl'));

$enviar = static function (array $evento, ?string $assinatura = null) use ($webhook, $SEGREDO): array {
    $payload = json_encode($evento, JSON_THROW_ON_ERROR);

    return $webhook->processar($payload, $assinatura ?? hash_hmac('sha256', $payload, $SEGREDO));
};

$statusAssinatura = static fn (): string => (string) $pdo->query('SELECT status FROM assinaturas')->fetchColumn();

titulo('Aula 2 — Checkout, gateway e webhooks');

secao('Você NUNCA processa número de cartão');

nota('Isso é PCI-DSS: responsabilidade legal e técnica pesada que todo gateway');
nota('sério resolve por você (checkout hospedado ou tokenização no client-side).');
nota('Seu papel é orquestrar o fluxo, não guardar dado de cartão.');

secao('O fluxo');

$passos = [
    '1. backend cria a intenção de cobrança na API do gateway',
    '2. gateway devolve uma URL; você redireciona o cliente',
    '3. o cliente paga NO GATEWAY, não no seu site',
    '4. gateway redireciona de volta (UX) E dispara um WEBHOOK (verdade)',
    '5. seu backend processa o webhook e atualiza a assinatura',
];
foreach ($passos as $p) {
    echo "  {$p}\n";
}
nota('O passo 4 é o que mais gente erra: o REDIRECT não é confiável para liberar');
nota('acesso — o cliente pode fechar a aba, a rede pode cair. A fonte de verdade');
nota('é sempre o webhook, que chega de forma assíncrona e independente.');

secao('ASSINATURA: a URL é pública, o segredo não');

$forjado = $enviar(
    ['id' => 'evt_ataque', 'type' => 'payment.succeeded', 'data' => ['assinatura_id' => $assinaturaId]],
    'assinatura-que-eu-inventei',
);

checa('payload forjado é recusado com 401', $forjado['status'] === 401, $forjado['body']['erro']);
checa('e a assinatura NÃO foi ativada', $statusAssinatura() === 'atrasada', 'continua atrasada');
checa('nem virou registro de evento', (int) $pdo->query('SELECT COUNT(*) FROM eventos_webhook')->fetchColumn() === 0, '');
nota('Sem verificação, qualquer um manda "pagamento aprovado" e libera acesso de graça.');

$fonte = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Billing/WebhookPagamento.php');
checa('a comparação usa hash_equals', str_contains($fonte, 'hash_equals'), 'tempo constante, não vaza por timing');

secao('Evento legítimo é aplicado');

$ok = $enviar(['id' => 'evt_001', 'type' => 'payment.succeeded', 'data' => ['assinatura_id' => $assinaturaId]]);

checa('200 processado', $ok['status'] === 200 && $ok['body']['status'] === 'processado', '');
checa('a assinatura foi ativada', $statusAssinatura() === 'ativa', '');

secao('IDEMPOTÊNCIA: gateways REENVIAM');

$repetido = $enviar(['id' => 'evt_001', 'type' => 'payment.succeeded', 'data' => ['assinatura_id' => $assinaturaId]]);

checa('o mesmo evento devolve ja_processado', $repetido['body']['status'] === 'ja_processado', '');
checa('e responde 200, não erro', $repetido['status'] === 200, 'para o gateway isto foi entregue com sucesso');
checa('só um registro na tabela', (int) $pdo->query('SELECT COUNT(*) FROM eventos_webhook')->fetchColumn() === 1, '');

$schema = (string) file_get_contents(__DIR__ . '/../crm-produto/migrations/20260901_0014_create_eventos_webhook_table.up.sql');
checa('quem garante isso é o UNIQUE do BANCO', str_contains($schema, 'UNIQUE KEY uq_eventos_externo'), 'não um if na aplicação');
nota('Se o seu servidor demora a responder 200, o gateway reenvia. Sem controle,');
nota('o mesmo pagamento é creditado duas ou três vezes.');

secao('Registro do evento e mudança na MESMA transação');

checa('o handler abre transação', str_contains($fonte, 'beginTransaction'), '');
checa('e faz rollback em falha', str_contains($fonte, 'rollBack'), '');
nota('Senão dá para ter evento marcado como processado sem a assinatura ter mudado.');

secao('Pagamento falhado');

$falha = $enviar(['id' => 'evt_002', 'type' => 'payment.failed', 'data' => ['assinatura_id' => $assinaturaId]]);
checa('marca a assinatura como atrasada', $statusAssinatura() === 'atrasada', "HTTP {$falha['status']}");
checa('e registra desde quando', $pdo->query('SELECT atrasada_desde FROM assinaturas')->fetchColumn() !== null, 'base do grace period');

secao('Evento que não interessa: 200, sem quebrar');

$ignorado = $enviar(['id' => 'evt_003', 'type' => 'invoice.viewed', 'data' => []]);
checa('tipo desconhecido responde 200', $ignorado['status'] === 200, '');
nota('Gateway envia muitos eventos. Responder erro faria ele reenviar para sempre —');
nota('qualquer coisa fora de 2xx é interpretada como falha de entrega.');

secao('Payload inválido');

checa('JSON quebrado dá 400', $webhook->processar('{ nao e json', hash_hmac('sha256', '{ nao e json', $SEGREDO))['status'] === 400, '');
checa('evento sem id dá 400', $enviar(['type' => 'payment.succeeded'])['status'] === 400, 'sem id não há idempotência possível');

secao('Corpo CRU, não reserializado');

$fonteReq = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Http/Request.php');
checa('o Request guarda o corpo original', str_contains($fonteReq, 'corpoCru'), '');
nota('A assinatura é do BYTE exato que chegou. Decodificar e reserializar muda');
nota('ordem de chaves e espaços — e o HMAC nunca mais bate.');

fecharAula();
