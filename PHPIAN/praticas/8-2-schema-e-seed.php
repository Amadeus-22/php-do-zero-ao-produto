<?php

// PHPIAN · Módulo 8 · Aula 2 — Modelagem e setup
// Prática: "Rode o SQL, configure db.php e insira um user admin com hash de senha."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 8-2 — schema e seed do Mini CRM');

$pdo = bancoDaPratica();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['contato_tag', 'tags', 'contatos', 'users'] as $t) {
    $pdo->exec("DROP TABLE IF EXISTS {$t}");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

secao('Rodar o SQL da aula');

$pdo->exec(<<<'SQL'
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL
)
SQL);
$pdo->exec(<<<'SQL'
CREATE TABLE contatos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NULL,
  telefone VARCHAR(30) NULL,
  notas TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
)
SQL);

checa('tabela users criada', (bool) $pdo->query("SHOW TABLES LIKE 'users'")->fetch());
checa('tabela contatos criada', (bool) $pdo->query("SHOW TABLES LIKE 'contatos'")->fetch());

$colUsers = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(), 'Field');
checa('users tem id, nome, email, senha_hash', $colUsers === ['id', 'nome', 'email', 'senha_hash']);

$colContatos = array_column($pdo->query('SHOW COLUMNS FROM contatos')->fetchAll(), 'Field');
checa('contatos tem os 7 campos', $colContatos === ['id', 'user_id', 'nome', 'email', 'telefone', 'notas', 'criado_em']);
checa('senha_hash é VARCHAR(255)', str_contains(
    (string) $pdo->query("SHOW COLUMNS FROM users LIKE 'senha_hash'")->fetch()['Type'], '255'),
    'bcrypt usa 60, mas argon2 usa ~96 — 255 dá folga para trocar de algoritmo');

secao('A chave estrangeira');

$fks = $pdo->query(<<<'SQL'
    SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'phpian' AND TABLE_NAME = 'contatos' AND REFERENCED_TABLE_NAME IS NOT NULL
SQL)->fetchAll();
checa('contatos.user_id referencia users', count($fks) === 1 && $fks[0]['REFERENCED_TABLE_NAME'] === 'users');

$erro = null;
try {
    $pdo->prepare('INSERT INTO contatos (user_id, nome) VALUES (?, ?)')->execute([999, 'Órfão']);
} catch (PDOException $e) {
    $erro = $e->getMessage();
}
checa('não dá para criar contato de usuário inexistente', str_contains((string) $erro, 'foreign key'),
    'é o que garante que todo contato tem dono');

secao('O seed do admin, com hash');

$semear = static function (PDO $pdo, string $nome, string $email, string $senha): int {
    $stmt = $pdo->prepare('INSERT INTO users (nome, email, senha_hash) VALUES (?, ?, ?)');
    // O hash é feito aqui, uma vez; a senha pura nunca chega ao banco.
    $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
    return (int) $pdo->lastInsertId();
};

$senhaAdmin = 'senha-de-estudo';
$admin = $semear($pdo, 'Admin', 'admin@exemplo.com', $senhaAdmin);
checa('admin criado', $admin === 1);

$linha = $pdo->query('SELECT * FROM users WHERE id = 1')->fetch();
checa('a senha pura NÃO está no banco', !str_contains(json_encode($linha), $senhaAdmin));
checa('senha_hash é bcrypt', str_starts_with($linha['senha_hash'], '$2y$'));
checa('password_verify aceita a senha certa', password_verify($senhaAdmin, $linha['senha_hash']));
checa('e recusa a errada', !password_verify('errada', $linha['senha_hash']));

secao('Seed idempotente');

// Rodar o seed duas vezes não pode duplicar nem estourar. O UNIQUE do e-mail é o
// guarda; o código precisa tratá-lo.
$semearSeNaoExiste = static function (PDO $pdo, string $email, string $senha) use ($semear): string {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch() !== false) {
        return 'já existia';
    }
    $semear($pdo, 'Admin', $email, $senha);
    return 'criado';
};

checa('segunda execução não duplica', $semearSeNaoExiste($pdo, 'admin@exemplo.com', $senhaAdmin) === 'já existia');
checa('continua 1 usuário', (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1);

secao('Um segundo usuário, para testar ownership na 8-3');

$vendedor = $semear($pdo, 'Vendedor', 'vendedor@exemplo.com', 'senha-de-estudo');
checa('segundo usuário criado', $vendedor === 2);
checa('e-mail duplicado é recusado', (static function () use ($pdo, $semear): bool {
    try { $semear($pdo, 'Outro', 'admin@exemplo.com', 'x'); return false; }
    catch (PDOException) { return true; }
})());

secao('O schema.sql do projeto entregue');

$sql = __DIR__ . '/../Modulo_8(modeagem_final)/mini-crm/sql/schema.sql';
checa('sql/schema.sql existe', is_file($sql), 'DDL em .sql, fora do PHP');
if (is_file($sql)) {
    $texto = (string) file_get_contents($sql);
    checa('cria a tabela users', stripos($texto, 'CREATE TABLE') !== false && stripos($texto, 'users') !== false);
    checa('cria a tabela contatos', stripos($texto, 'contatos') !== false);
    checa('guarda senha_hash, não senha', stripos($texto, 'senha_hash') !== false);
}

$seed = __DIR__ . '/../Modulo_8(modeagem_final)/mini-crm/scripts/seed.php';
checa('scripts/seed.php existe', is_file($seed));
if (is_file($seed)) {
    checa('o seed usa password_hash', str_contains((string) file_get_contents($seed), 'password_hash'),
        'nem o seed grava senha pura');
}

fecharPratica();
