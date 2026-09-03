<?php

// PHPAN · Módulo 7 · Aula 02 — Migrações de banco
// metadados em aulas.json · a ideia em 02-migracoes-banco.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Config\Config;
use App\Support\Database;

Config::carregar();
$pdo = Database::conexao();
$raiz = __DIR__ . '/../crm-produto';

$rodar = static fn (string $cmd): string => (string) shell_exec("cd " . escapeshellarg($raiz) . " && php bin/migrate.php {$cmd} 2>&1");

titulo('Aula 2 — Migrações de banco');

secao('As três propriedades');

printf("  %-14s %s\n", 'Versionada', 'tem ordem e identidade (prefixo YYYYMMDD_NNNN)');
printf("  %-14s %s\n", 'Rastreada', 'o banco sabe o que já rodou (tabela migrations)');
printf("  %-14s %s\n", 'Reversível', 'toda up tem uma down');

secao('SQL em arquivo .sql, não em heredoc dentro do PHP');

$ups = glob($raiz . '/migrations/*.up.sql') ?: [];
$downs = glob($raiz . '/migrations/*.down.sql') ?: [];

checa('migrações são arquivos .sql', count($ups) > 0, count($ups) . ' migrações');
checa('cada up tem sua down', count($ups) === count($downs), count($downs) . ' arquivos de reversão');
checa('nenhum .php na pasta de migrações', glob($raiz . '/migrations/*.php') === [], 'schema é SQL');
nota('Heredoc esconde o SQL do editor, do diff e de qualquer ferramenta de banco.');
nota('O runner continua PHP porque o que ele faz é lógica, não schema.');

secao('Ordem determinística vem do NOME');

$nomes = array_map(static fn (string $p): string => basename($p, '.up.sql'), $ups);
sort($nomes);
foreach (array_slice($nomes, 0, 4) as $n) {
    echo "  {$n}\n";
}
checa('prefixo YYYYMMDD_NNNN em todas', count(array_filter($nomes, static fn (string $n): bool => (bool) preg_match('/^\d{8}_\d{4}_/', $n))) === count($nomes), 'evita colisão no mesmo dia');

secao('Rastreada: rodar de novo é seguro');

$saida = $rodar('up');
checa('segunda execução não reaplica nada', str_contains($saida, 'Nada a aplicar'), trim($saida));

$aplicadas = (int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn();
checa('a tabela migrations tem o histórico', $aplicadas === count($ups), "{$aplicadas} registros");

secao('status mostra o que falta');

echo trim($rodar('status')), "\n";

secao('down e up de volta');

$antes = (int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn();
$saidaDown = $rodar('down');
$depois = (int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn();

checa('down reverte a última', $depois === $antes - 1, trim($saidaDown));
$rodar('up');
checa('up recoloca', (int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn() === $antes, '');

secao('DIVERGÊNCIA da aula: transação em DDL não funciona no MySQL');

$runner = php_strip_whitespace($raiz . '/bin/migrate.php');
checa('o runner NÃO usa beginTransaction em DDL', !str_contains($runner, 'beginTransaction'), '');

// Prova do commit implícito:
$pdo->exec('DROP TABLE IF EXISTS teste_ddl');
$pdo->beginTransaction();
$pdo->exec('CREATE TABLE teste_ddl (id INT)');
$aindaEmTransacao = $pdo->inTransaction();
$pdo->exec('DROP TABLE IF EXISTS teste_ddl');

checa(
    'após um CREATE TABLE, a transação já morreu',
    !$aindaEmTransacao,
    'commit implícito do MySQL',
);
nota('A aula envolve cada migração numa transação. Em PostgreSQL funciona.');
nota('No MySQL o commit() seguinte estoura "There is no active transaction" —');
nota('com a tabela JÁ criada. Foi exatamente o que aconteceu ao rodar aqui.');
nota('Consequência: UMA alteração estrutural por migração, e backup antes.');

secao('Migração de schema NÃO carrega dado de negócio');

$temInsert = array_filter($ups, static fn (string $p): bool => (bool) preg_match('/INSERT INTO/i', (string) file_get_contents($p)));
checa('nenhuma migração faz INSERT de dado', $temInsert === [], 'isso é seed: bin/seed-usuarios.php');

secao('Regra que não se quebra');

nota('Migração já aplicada em algum ambiente é HISTÓRICO. Mudar o conteúdo sem');
nota('mudar o nome faz cada ambiente ter um schema diferente do que o arquivo diz.');
nota('Achou erro? Crie uma migração nova de ajuste.');

fecharAula();
