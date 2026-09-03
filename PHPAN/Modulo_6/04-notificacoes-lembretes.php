<?php

// PHPAN · Módulo 6 · Aula 04 — Notificações e lembretes (agenda do CRM)
// metadados em aulas.json · a ideia em 04-notificacoes-lembretes.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;
use App\Support\Container;

$pdo = bancoDaAula();

$usuario = Container::repositorioDeUsuarios()->salvar(
    Usuario::novo('Ana Vendedora', 'ana@exemplo.com', 'senha-de-estudo', Papel::VENDEDOR),
);
$cliente = Container::clienteService()->criar(['nome' => 'Cliente Alfa', 'email' => 'alfa@exemplo.com']);
$uid = (int) $usuario->id();
$cid = (int) $cliente->id();

$service = Container::lembreteService();

titulo('Aula 4 — Notificações e lembretes');

secao('FUSO: grava em UTC, converte só na exibição');

// O vendedor digita "09:00 de um dia futuro" no fuso DELE.
// Data RELATIVA de propósito: com data fixa, a aula passa hoje e falha amanhã —
// o lembrete "futuro" vira vencido e o cron despacha dois em vez de um.
$dia = (new DateTimeImmutable('+3 days'))->format('Y-m-d');
$local = new DateTimeImmutable("{$dia} 09:00:00", new DateTimeZone('America/Sao_Paulo'));
$id = $service->criar($uid, $cid, 'Ligar para fechar proposta', $local);

$gravado = $pdo->query("SELECT vence_em FROM lembretes WHERE id = {$id}")->fetchColumn();

checa('o banco guarda em UTC', $gravado === "{$dia} 12:00:00", "digitado 09:00 -03 -> {$gravado} UTC");

$pendentes = $service->pendentesDe($uid);
$esperado = (new DateTimeImmutable($dia))->format('d/m/Y') . ' 09:00';
checa('a exibição converte de volta', $pendentes[0]['vence_em_local'] === $esperado, $pendentes[0]['vence_em_local']);

nota('Salvar hora local funciona no seu computador e quebra sutilmente quando');
nota('servidor e usuário estão em fusos diferentes, ou no horário de verão.');

secao('O cron despacha só o que venceu');

$service->criar($uid, $cid, 'Já venceu ontem', new DateTimeImmutable('-1 day'));
$service->criar($uid, $cid, 'Só semana que vem', new DateTimeImmutable('+7 days'));

$despachados = $service->despacharVencidos();

checa('despachou apenas o vencido', $despachados === 1, "{$despachados} lembrete(s)");
checa('virou job na fila', (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE tipo = 'notificar_lembrete'")->fetchColumn() === 1, '');

$status = $pdo->query('SELECT mensagem, status FROM lembretes ORDER BY id')->fetchAll();
foreach ($status as $l) {
    printf("  %-28s %s\n", mb_substr((string) $l['mensagem'], 0, 26), $l['status']);
}

secao('IDEMPOTÊNCIA: rodar o cron duas vezes não duplica');

$segundaRodada = $service->despacharVencidos();

checa('segunda execução não despacha nada', $segundaRodada === 0, '');
checa('e a fila continua com 1 job', (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE tipo = 'notificar_lembrete'")->fetchColumn() === 1, '');

// O SQL vive no REPOSITÓRIO, não no service: o LembreteService estava em
// Application/ conversando com PDO direto, e a aula 3 do Módulo 1 (que varre a
// pasta procurando PDO) pegou a violação. Hoje há RepositorioDeLembretes no
// domínio e a implementação PDO na infraestrutura.
$fonte = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Infrastructure/Lembrete/RepositorioDeLembretesPdo.php');
$fonteService = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Application/Lembrete/LembreteService.php');
checa('a query usa FOR UPDATE SKIP LOCKED', str_contains($fonte, 'FOR UPDATE SKIP LOCKED'), '');
checa('e marca notificado na MESMA transação', str_contains($fonte, "UPDATE lembretes SET status = 'notificado'"), '');
checa('o Service NÃO conhece PDO', !str_contains($fonteService, 'PDO'), 'aplicação depende da interface');
nota('Marcar fora da transação que selecionou permite dois crons notificarem juntos.');

secao('Lembrete atrasado NÃO some');

checa('a condição é vence_em <= UTC_TIMESTAMP()', str_contains($fonte, 'vence_em <= UTC_TIMESTAMP()'), '');
nota('Com "=" em vez de "<=", um cron que ficou 10 minutos fora do ar perderia');
nota('para sempre os lembretes daquele intervalo.');

secao('Quem envia é o WORKER, não o cron');

$worker = Container::worker();
$worker->processarProximo();

$emails = (string) @file_get_contents(dirname(__DIR__) . '/crm-produto/var/emails.log');
checa('o e-mail do lembrete foi enviado', str_contains($emails, 'Ligar') || str_contains($emails, 'Lembrete'), 'pelo worker');
nota('O cron termina rápido e o envio ganha retry e backoff de graça (aula 2).');

secao('Concluir tira da lista');

$service->concluir($id, $uid);
$restantes = array_column($service->pendentesDe($uid), 'mensagem');

checa('o lembrete concluído sai dos pendentes', !in_array('Ligar para fechar proposta', $restantes, true), count($restantes) . ' restantes');

fecharAula();
