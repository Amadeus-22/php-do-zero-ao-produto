<?php

// PHPIAN · Módulo 6 · Aula 2 — Conexão PDO segura
// Prática: "Crie db.php que retorna uma instância PDO configurada como acima."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 6-2 — db.php');

secao('O arquivo');

$raiz = areaTemporaria('6-2');
file_put_contents($raiz . '/db.php', <<<'PHP'
<?php

declare(strict_types=1);

// Um arquivo, uma conexão. Todo o resto do sistema faz:
//   $pdo = require __DIR__ . '/db.php';
// Credenciais vêm do ambiente — nunca ficam no arquivo que vai para o Git.
$host = getenv('DB_HOST') ?: '127.0.0.1';
$porta = getenv('DB_PORT') ?: '3306';
$banco = getenv('DB_NAME') ?: 'phpian';
$usuario = getenv('DB_USER') ?: 'root';
$senha = getenv('DB_PASS') ?: '';

$dsn = "mysql:host={$host};port={$porta};dbname={$banco};charset=utf8mb4";

return new PDO($dsn, $usuario, $senha, [
    // Erro vira exceção, em vez de devolver false silenciosamente
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // fetch() já devolve array associativo, sem índices numéricos duplicados
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Prepared statement DE VERDADE, feito pelo MySQL — não emulado com escape
    PDO::ATTR_EMULATE_PREPARES => false,
]);
PHP);

putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3307');
putenv('DB_NAME=phpian');
putenv('DB_USER=crm');
putenv('DB_PASS=crm-estudo');

bancoDaPratica();   // garante que o banco phpian existe
$pdo = require $raiz . '/db.php';

checa('db.php retorna um PDO', $pdo instanceof PDO);
checa('a conexão está viva', (int) $pdo->query('SELECT 1')->fetchColumn() === 1);
checa('conectou no banco certo', $pdo->query('SELECT DATABASE()')->fetchColumn() === 'phpian');

secao('As três opções, uma a uma');

checa('ERRMODE_EXCEPTION', $pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION);
checaExcecao('SQL inválido LANÇA em vez de devolver false', \PDOException::class,
    static fn () => $pdo->query('SELECT * FROM tabela_que_nao_existe'));

checa('FETCH_ASSOC', $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE) === PDO::FETCH_ASSOC);
$pdo->exec('DROP TABLE IF EXISTS contatos_teste');
$pdo->exec('CREATE TABLE contatos_teste (id INT PRIMARY KEY, nome VARCHAR(50))');
$pdo->exec("INSERT INTO contatos_teste VALUES (1, 'Ana')");
$linha = $pdo->query('SELECT * FROM contatos_teste')->fetch();
checa('a linha vem só com chaves nomeadas', array_keys($linha) === ['id', 'nome'], json_encode($linha));

checa('EMULATE_PREPARES desligado', $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES) === false);

secao('Por que EMULATE_PREPARES = false importa');

// Com emulação, o PDO monta a query como string e escapa no cliente. Sem, o SQL
// vai ao servidor com placeholder e o dado vai separado — não há como o dado
// virar comando, seja qual for o charset.
$stmt = $pdo->prepare('SELECT * FROM contatos_teste WHERE nome = ?');
$stmt->execute(["Ana'; DROP TABLE contatos_teste; --"]);
checa('injeção clássica não executa nada', $stmt->fetch() === false, 'foi tratada como texto de busca');
checa('a tabela continua de pé', (bool) $pdo->query("SHOW TABLES LIKE 'contatos_teste'")->fetch());

// Com emulação ligada, o int vira string na volta
$emulado = new PDO('mysql:host=127.0.0.1;port=3307;dbname=phpian;charset=utf8mb4', 'crm', 'crm-estudo', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => true,
]);
// Tem de ser prepare()/execute(): em query() o mysqlnd já devolve tipo nativo
// nos dois casos, e a diferença não apareceria.
$s1 = $emulado->prepare('SELECT id FROM contatos_teste WHERE id = ?');
$s1->execute([1]);
$comEmulacao = $s1->fetch();
$s2 = $pdo->prepare('SELECT id FROM contatos_teste WHERE id = ?');
$s2->execute([1]);
$semEmulacao = $s2->fetch();
// Até o PHP 8.0, a emulação devolvia tudo como string e isso quebrava === e
// json_encode. Desde o 8.1 os dois modos devolvem tipo nativo — a diferença
// deixou de existir, e vale saber disso antes de repetir o conselho antigo.
checa('com emulação, o INT volta como int (PHP 8.1+)', is_int($comEmulacao['id']), var_export($comEmulacao['id'], true));
checa('sem emulação, também', is_int($semEmulacao['id']), var_export($semEmulacao['id'], true));

$stringificado = new PDO('mysql:host=127.0.0.1;port=3307;dbname=phpian;charset=utf8mb4', 'crm', 'crm-estudo', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_STRINGIFY_FETCHES => true,
]);
$s3 = $stringificado->prepare('SELECT id FROM contatos_teste WHERE id = ?');
$s3->execute([1]);
checa('só STRINGIFY_FETCHES força string hoje', is_string($s3->fetch()['id']));
nota('o ganho real de EMULATE_PREPARES=false é o SQL ir separado do dado, não a tipagem');

secao('charset=utf8mb4 no DSN');

$pdo->exec('DROP TABLE IF EXISTS acentos');
$pdo->exec('CREATE TABLE acentos (t VARCHAR(50)) CHARACTER SET utf8mb4');
$pdo->prepare('INSERT INTO acentos VALUES (?)')->execute(['Ação 🚀 Ñoño']);
checa('acento e emoji sobrevivem à ida e volta',
    $pdo->query('SELECT t FROM acentos')->fetchColumn() === 'Ação 🚀 Ñoño');

secao('"Nunca deixe senha de produção no código público"');

$codigo = (string) file_get_contents($raiz . '/db.php');
checa('db.php não tem senha literal', !str_contains($codigo, 'crm-estudo'));
checa('as credenciais vêm de getenv', substr_count($codigo, 'getenv(') === 5);
checa('há padrão para o localhost', str_contains($codigo, "?: '127.0.0.1'"), 'funciona na máquina do aluno sem configurar nada');

$pdo->exec('DROP TABLE IF EXISTS contatos_teste');
$pdo->exec('DROP TABLE IF EXISTS acentos');

fecharPratica();
