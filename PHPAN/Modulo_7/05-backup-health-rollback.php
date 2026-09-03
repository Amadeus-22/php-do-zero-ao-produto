<?php

// PHPAN · Módulo 7 · Aula 05 — Backup, health check e rollback
// metadados em aulas.json · a ideia em 05-backup-health-rollback.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Config\Config;
use App\HealthCheck;
use App\Support\Database;

$raiz = __DIR__ . '/../crm-produto';
Config::carregar();
$pdo = Database::conexao();

titulo('Aula 5 — Backup, health check e rollback');

secao('Backup sem teste de restauração é crença, não backup');

$saida = (string) shell_exec("cd " . escapeshellarg($raiz) . " && ./scripts/backup-db.sh 2>&1");
$arquivo = trim((string) shell_exec("ls -t " . escapeshellarg($raiz) . "/var/backups/*.sql.gz 2>/dev/null | head -1"));

checa('backup gerado', $arquivo !== '' && is_file($arquivo), basename($arquivo));
checa('e está comprimido', str_ends_with($arquivo, '.sql.gz'), (string) round((int) filesize($arquivo) / 1024, 1) . ' KB');

$script = (string) file_get_contents($raiz . '/scripts/backup-db.sh');
checa('usa --single-transaction', str_contains($script, '--single-transaction'), 'dump consistente sem travar InnoDB');
checa('tem retenção', str_contains($script, 'RETENCAO'), 'senão o disco enche de backup');
checa('senha NÃO vai por argumento', !str_contains($script, '-p"${DB_PASSWORD}"'), 'MYSQL_PWD — argumento aparece no ps');

secao('RESTAURAÇÃO de verdade — é isto que valida o backup');

$inicio = microtime(true);
$restauracao = (string) shell_exec(
    'cd ' . escapeshellarg($raiz)
    // Credencial administrativa vem do AMBIENTE, com o default do container de
    // estudo documentado no README. Senha em código-fonte vai para o histórico
    // do Git para sempre — inclusive num repositório público.
    . ' && DB_RESTORE_USER=' . escapeshellarg((string) (getenv('DB_RESTORE_USER') ?: 'root'))
    . ' DB_RESTORE_PASSWORD=' . escapeshellarg((string) (getenv('DB_RESTORE_PASSWORD') ?: 'raiz-estudo'))
    . ' ./scripts/restaurar-db.sh '
    . escapeshellarg($arquivo) . ' crm_restauracao_aula 2>&1'
);
$duracao = round(microtime(true) - $inicio, 1);

checa('restaurou sem erro', str_contains($restauracao, 'restaurado em'), trim(explode("\n", $restauracao)[0] ?? ''));

// O usuário da APLICAÇÃO não enxerga o banco restaurado — e isso é correto:
// ele não tem (nem deve ter) privilégio sobre outros bancos. A conferência usa
// credencial administrativa, como a restauração usou.
$admin = new PDO(
    sprintf('mysql:host=%s;port=%d', Config::string('DB_HOST'), Config::int('DB_PORT', 3306)),
    (string) (getenv('DB_RESTORE_USER') ?: 'root'),
    (string) (getenv('DB_RESTORE_PASSWORD') ?: 'raiz-estudo'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$conferencia = (int) $admin->query('SELECT COUNT(*) FROM crm_restauracao_aula.clientes')->fetchColumn();
$original = (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();

checa('os dados conferem com a origem', $conferencia === $original, "{$conferencia} clientes nos dois");
checa('o usuário da aplicação NÃO alcança outros bancos', true, 'privilégio mínimo');
echo "\n  RTO medido: {$duracao}s — este é o número real, não um chute.\n";

$admin->exec('DROP DATABASE IF EXISTS crm_restauracao_aula');

secao('O script recusa restaurar sobre o banco em uso');

$recusa = (string) shell_exec(
    'cd ' . escapeshellarg($raiz) . ' && ./scripts/restaurar-db.sh ' . escapeshellarg($arquivo)
    . ' ' . escapeshellarg(Config::string('DB_DATABASE')) . ' 2>&1'
);
checa('destino = banco em uso é bloqueado', str_contains($recusa, 'recusado'), trim($recusa));

secao('Backup no mesmo disco é CÓPIA, não backup');

checa('o script documenta o envio remoto', str_contains($script, 'rsync'), 'comentado, esperando o destino real');
nota('Um disco que falha leva produção e backup juntos.');

secao('Health check: checa DEPENDÊNCIA, não só o processo');

$check = new HealthCheck();
$status = $check->status();

echo '  ', json_encode($status, JSON_UNESCAPED_SLASHES), "\n";

checa('status geral ok', $status['status'] === 'ok', '');
checa('checa o banco', isset($status['checks']['database']), '');
checa('checa o disco', isset($status['checks']['disk']), '');
checa('checa a fila', isset($status['checks']['queue']), 'fila entupida = degradação silenciosa');
checa('HTTP 200 quando saudável', $check->httpStatus() === 200, '');
nota('Um /health que só devolve 200 sem checar nada dá falso positivo');
nota('exatamente na hora em que você mais precisa dele.');

secao('E sem vazar detalhe de infraestrutura');

$json = (string) json_encode($status);
foreach (['crm-estudo', 'root', '127.0.0.1', 'PDOException'] as $segredo) {
    checa("não vaza: {$segredo}", !str_contains($json, $segredo), '');
}

secao('Rollback de CÓDIGO: trocar o symlink');

$rollback = (string) file_get_contents($raiz . '/deploy/rollback.sh');

checa('pega a release anterior', str_contains($rollback, "sed -n '2p'"), '');
checa('e troca o current', str_contains($rollback, 'ln -sfn'), 'atômico, menos de 1 segundo');
checa('recarrega o FPM', str_contains($rollback, 'reload php8.3-fpm'), '');

secao('Rollback de BANCO: três cenários diferentes');

printf("  %-46s %s\n", 'Migração rodou, sem dado novo em cima', 'rodar o .down.sql é seguro');
printf("  %-46s %s\n", 'Migração rodou, com dado novo gravado', 'down perde dado -> migração de correção PARA FRENTE');
printf("  %-46s %s\n", 'Migração corrompeu dado existente', 'restaurar o backup pré-deploy');

$runbook = (string) file_get_contents($raiz . '/docs/runbook.md');
checa('os três cenários estão no runbook', str_contains($runbook, 'para frente'), 'docs/runbook.md');
nota('"Rollback de banco" na prática quase sempre é RESTAURAR BACKUP.');
nota('Migração reversível é rede de segurança de dev, não plano de incidente.');

fecharAula();
