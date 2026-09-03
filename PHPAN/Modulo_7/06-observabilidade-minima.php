<?php

// PHPAN · Módulo 7 · Aula 06 — Observabilidade mínima
// metadados em aulas.json · a ideia em 06-observabilidade-minima.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Log\Logger;

$raiz = __DIR__ . '/../crm-produto';
$log = sys_get_temp_dir() . '/aula-obs.jsonl';
@unlink($log);

titulo('Aula 6 — Observabilidade mínima');

secao('As três perguntas, nesta ordem de prioridade');

printf("  1. %-42s %s\n", 'Está no ar?', 'monitor externo batendo no /health');
printf("  2. %-42s %s\n", 'Fico sabendo quando quebra, e rápido?', 'log + alerta');
printf("  3. %-42s %s\n", 'Consigo entender depois do fato?', 'log com contexto');
nota('Tracing distribuído e métrica por percentil é over-engineering aqui.');
nota('Fica para o PHPPRO, quando a escala pedir.');

secao('Monitor EXTERNO — a parte que não pode rodar no seu servidor');

nota('Se o próprio servidor monitora a si mesmo e cai, ninguém fica sabendo.');
nota('UptimeRobot/Better Stack/Healthchecks batendo em /health a cada 5 min.');
nota('Ainda não configurado: não há domínio público. Declarado, não simulado.');

secao('Alerta de taxa de erro — sem ferramenta paga');

$logger = new Logger($log);

for ($i = 0; $i < 3; $i++) {
    $logger->info('requisição atendida', ['rota' => '/clientes']);
}

$saida = (string) shell_exec(
    'LOG_FILE=' . escapeshellarg($log) . ' LIMITE_ERROS=10 '
    . escapeshellarg($raiz . '/scripts/checar-taxa-erro.sh') . ' 2>&1'
);
checa('log tranquilo não alerta', str_contains($saida, 'erros nos últimos'), trim($saida));

for ($i = 0; $i < 12; $i++) {
    $logger->error('falha ao salvar cliente', ['excecao' => 'PDOException']);
}

$saidaAlerta = (string) shell_exec(
    'LOG_FILE=' . escapeshellarg($log) . ' LIMITE_ERROS=10 '
    . escapeshellarg($raiz . '/scripts/checar-taxa-erro.sh') . ' 2>&1; echo "exit=$?"'
);
checa('acima do limite ALERTA', str_contains($saidaAlerta, 'ALERTA'), trim(explode("\n", $saidaAlerta)[1] ?? ''));
checa('e sai com código de erro (o cron detecta)', str_contains($saidaAlerta, 'exit=1'), '');
nota('Rústico e funcional — muito mais barato, em dinheiro e complexidade,');
nota('do que integrar APM num sistema com um punhado de usuários.');

secao('Por que JSON Lines torna isso possível');

$linhas = array_filter(explode("\n", (string) file_get_contents($log)));
$erros = array_filter($linhas, static fn (string $l): bool => str_contains($l, '"nivel":"error"'));

checa('filtrar por nível é um grep', count($erros) === 12, count($erros) . ' linhas de erro');
checa('e a data está em cada linha', str_contains((string) reset($linhas), '"timestamp"'), 'dá para cortar por janela');

secao('Rotação — senão o disco enche');

$logrotate = (string) file_get_contents($raiz . '/deploy/logrotate.conf');

checa('config de logrotate versionada', $logrotate !== '', 'deploy/logrotate.conf');
checa('rotação diária', str_contains($logrotate, 'daily'), '');
checa('mantém 14 dias', str_contains($logrotate, 'rotate 14'), '');
checa('comprime os antigos', str_contains($logrotate, 'compress'), '');
nota('Disco cheio derruba a aplicação inteira — inclusive a escrita de novos logs.');
nota('O incidente vira "servidor travado por log", pior que o bug original.');

secao('O que logar e o que NÃO logar');

printf("  %-42s %s\n", 'LOGAR', 'NÃO LOGAR');
printf("  %-42s %s\n", 'falha de autenticação (sem a senha)', 'senha, token, cartão');
printf("  %-42s %s\n", 'exceção não tratada', 'payload de webhook com dado sensível');
printf("  %-42s %s\n", 'falha de envio de e-mail/fila', 'CPF em texto puro sem necessidade');
printf("  %-42s %s\n", 'requisição lenta (acima do limiar)', 'corpo inteiro de toda requisição');

$logger->error('tentativa de login', ['email' => 'ana@exemplo.com', 'senha' => 'segredo']);
$ultima = (string) end($linhas);
$linhasAgora = array_values(array_filter(explode("\n", (string) file_get_contents($log))));
$ultimaLinha = json_decode((string) end($linhasAgora), true);

checa('o filtro do Logger remove a senha', $ultimaLinha['contexto']['senha'] === '[REMOVIDO]', '');
nota('Log fica semanas em disco e é lido por mais gente que o banco.');

secao('Alerta que ninguém olha = sem alerta');

nota('Configurar e-mail de alerta para caixa não checada é o mesmo que não ter.');
nota('No script, o envio real (mail/webhook) está marcado no ponto certo.');

@unlink($log);
fecharAula();
