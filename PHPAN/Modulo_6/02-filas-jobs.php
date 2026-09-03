<?php

// PHPAN · Módulo 6 · Aula 02 — Filas e jobs (e-mail, relatórios)
// metadados em aulas.json · a ideia em 02-filas-jobs.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Filas\JobDispatcher;
use App\Filas\JobHandler;
use App\Filas\Worker;
use App\Log\Logger;

$pdo = bancoDaAula();
$pdo->exec('DELETE FROM jobs');

$logArquivo = sys_get_temp_dir() . '/aula-fila.jsonl';
@unlink($logArquivo);

$dispatcher = new JobDispatcher($pdo);
$ultimoJob = static fn (): array => (array) $pdo->query('SELECT * FROM jobs ORDER BY id DESC LIMIT 1')->fetch();

/** Handler que conta execuções e pode falhar de propósito. */
$espiao = new class implements JobHandler {
    public int $execucoes = 0;

    public ?string $falharCom = null;

    public function tratar(array $payload): void
    {
        $this->execucoes++;

        if ($this->falharCom !== null) {
            throw new RuntimeException($this->falharCom);
        }
    }
};

$worker = static fn (): Worker => new Worker($pdo, ['teste' => $espiao], new Logger($logArquivo));

titulo('Aula 2 — Filas e jobs');

secao('O problema: e-mail dentro do request');

nota('Mandar e-mail em "criar cliente" faz o usuário esperar o SMTP responder —');
nota('e obriga a decidir na hora se uma falha de envio derruba a criação.');
nota('Nenhuma das duas respostas é boa. A fila desfaz o dilema.');

secao('Despachar = registrar a intenção e devolver a resposta');

$id = $dispatcher->despachar('teste', ['cliente_id' => 42]);
$job = $ultimoJob();

checa('job nasce pendente', $job['status'] === 'pendente', '');
checa('com zero tentativas', (int) $job['tentativas'] === 0, '');
checa('payload guardado como JSON', json_decode((string) $job['payload'], true)['cliente_id'] === 42, '');
checa('disponível para processar agora', $job['disponivel_em'] <= date('Y-m-d H:i:s'), '');

secao('O worker processa');

checa('processarProximo() encontra trabalho', $worker()->processarProximo(), '');
checa('o handler executou', $espiao->execucoes === 1, "{$espiao->execucoes} execução");
checa('status vira concluido', $ultimoJob()['status'] === 'concluido', '');
checa('e concluido_em é preenchido', $ultimoJob()['concluido_em'] !== null, '');
checa('fila vazia devolve false', !$worker()->processarProximo(), 'nada a fazer, sem erro');

secao('Falha: retry com BACKOFF, não loop imediato');

$espiao->falharCom = 'SMTP fora do ar';
$espiao->execucoes = 0;
$dispatcher->despachar('teste', []);
$worker()->processarProximo();

$job = $ultimoJob();
checa('volta para pendente, não morre na 1ª falha', $job['status'] === 'pendente', '');
checa('tentativas = 1', (int) $job['tentativas'] === 1, '');
checa('o erro fica registrado', str_contains((string) $job['erro'], 'SMTP'), '');
checa('disponivel_em foi jogado para o futuro', $job['disponivel_em'] > date('Y-m-d H:i:s'), 'backoff exponencial');
checa('e por isso ele NÃO é pego de novo agora', !$worker()->processarProximo(), '');

nota('Backoff: 60s, 120s, 240s... Retentar em loop imediato só multiplica a falha.');

secao('DEAD-LETTER: para de tentar depois de ' . Worker::MAX_TENTATIVAS);

for ($i = 1; $i < Worker::MAX_TENTATIVAS; $i++) {
    $pdo->exec('UPDATE jobs SET disponivel_em = NOW()'); // encurta o backoff na aula
    $worker()->processarProximo();
}

$job = $ultimoJob();
checa('status final é falhou', $job['status'] === 'falhou', '');
checa('parou em ' . Worker::MAX_TENTATIVAS . ' tentativas', (int) $job['tentativas'] === Worker::MAX_TENTATIVAS, '');
$pdo->exec('UPDATE jobs SET disponivel_em = NOW()');
checa('e não é mais retentado', !$worker()->processarProximo(), 'fica para alguém olhar');

secao('Concorrência: SKIP LOCKED');

$fonte = (string) file_get_contents(__DIR__ . '/../crm-produto/src/Filas/Worker.php');
checa('a query usa FOR UPDATE SKIP LOCKED', str_contains($fonte, 'FOR UPDATE SKIP LOCKED'), '');
nota('Sem isso, dois workers pegam o MESMO job e o e-mail sai duas vezes.');

secao('Idempotência é responsabilidade do HANDLER');

$relatorio = __DIR__ . '/../crm-produto/src/Filas/Handlers/GerarRelatorioClientes.php';
checa(
    'o gerador de relatório checa se o arquivo já existe',
    str_contains((string) file_get_contents($relatorio), 'if (is_file($caminho))'),
    'retry não recria o que já foi gerado',
);
nota('E-mail transacional é naturalmente quase-idempotente; "gerar relatório" não é.');

secao('Handler desconhecido não trava o worker');

$dispatcher->despachar('tipo_inexistente', []);
$semHandler = new Worker($pdo, [], new Logger($logArquivo));

checa('o worker segue vivo', $semHandler->processarProximo(), '');
checa('e o job é reagendado', $ultimoJob()['status'] === 'pendente', 'entra no ciclo de retry');

secao('O worker precisa estar RODANDO');

nota('php bin/worker.php  (supervisor/systemd em produção — Módulo 7)');
nota('Sem ele de pé, os jobs só se acumulam como pendente e nada acontece.');

@unlink($logArquivo);
fecharAula();
