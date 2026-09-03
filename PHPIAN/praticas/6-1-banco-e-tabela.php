<?php

// PHPIAN · Módulo 6 · Aula 1 — Bancos relacionais em 15 minutos
// Prática: "Crie o banco phpian e a tabela contatos."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 6-1 — banco phpian e tabela contatos');

$pdo = bancoDaPratica();

secao('O banco');

$atual = $pdo->query('SELECT DATABASE()')->fetchColumn();
checa('conectado ao banco phpian', $atual === 'phpian', (string) $atual);
$charset = $pdo->query("SELECT @@character_set_database")->fetchColumn();
checa('charset utf8mb4', str_starts_with((string) $charset, 'utf8mb4'), (string) $charset . ' — sem isso emoji e acento quebram');

secao('A tabela, como no SQL da aula');

$pdo->exec('DROP TABLE IF EXISTS contato_tag');
$pdo->exec('DROP TABLE IF EXISTS contatos');
$pdo->exec(<<<'SQL'
CREATE TABLE contatos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  telefone VARCHAR(30) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL);

checa('a tabela existe', (bool) $pdo->query("SHOW TABLES LIKE 'contatos'")->fetch());

$colunas = [];
foreach ($pdo->query('SHOW COLUMNS FROM contatos') as $c) {
    $colunas[$c['Field']] = $c;
}
checa('tem as 5 colunas', array_keys($colunas) === ['id', 'nome', 'email', 'telefone', 'criado_em']);

secao('Cada regra do schema, provada com um INSERT');

// PRIMARY KEY + AUTO_INCREMENT
$pdo->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)')->execute(['Ana Souza', 'ana@exemplo.com']);
$id = (int) $pdo->lastInsertId();
checa('AUTO_INCREMENT deu o id 1', $id === 1, "id={$id}");
$pdo->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)')->execute(['Bruno Lima', 'bruno@exemplo.com']);
checa('o segundo recebeu id 2', (int) $pdo->lastInsertId() === 2);
checa('a chave primária é id', $colunas['id']['Key'] === 'PRI');

// NOT NULL
$erro = null;
try {
    $pdo->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)')->execute([null, 'x@exemplo.com']);
} catch (PDOException $e) {
    $erro = $e->getMessage();
}
checa('nome NOT NULL é respeitado', str_contains((string) $erro, 'cannot be null'), 'o banco recusa, não o PHP');

// UNIQUE
$erroUnico = null;
try {
    $pdo->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)')->execute(['Outra Ana', 'ana@exemplo.com']);
} catch (PDOException $e) {
    $erroUnico = $e->getMessage();
}
checa('email UNIQUE bloqueia duplicata', str_contains((string) $erroUnico, 'Duplicate entry'),
    'a unicidade mora no banco, não num foreach do PHP');
checa('continuam 2 contatos', (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn() === 2);

// NULL permitido
$pdo->prepare('INSERT INTO contatos (nome, email, telefone) VALUES (?, ?, ?)')->execute(['Célia Reis', 'celia@exemplo.com', null]);
$celia = $pdo->query("SELECT * FROM contatos WHERE email = 'celia@exemplo.com'")->fetch();
checa('telefone aceita NULL', $celia['telefone'] === null, 'é opcional por design');

// DEFAULT CURRENT_TIMESTAMP
checa('criado_em foi preenchido sozinho', !empty($celia['criado_em']), $celia['criado_em']);
checa('e é de agora', abs(time() - strtotime($celia['criado_em'])) < 120);

// VARCHAR(120)
$erroTamanho = null;
try {
    $pdo->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)')->execute([str_repeat('a', 121), 'longo@exemplo.com']);
} catch (PDOException $e) {
    $erroTamanho = $e->getMessage();
}
checa('VARCHAR(120) recusa 121 caracteres', str_contains((string) $erroTamanho, 'too long'), 'em modo estrito o MySQL recusa em vez de truncar');

secao('O vocabulário da aula');

checa('tabela = contatos', true);
checa('linha = um contato', count($pdo->query('SELECT * FROM contatos')->fetchAll()) === 3);
checa('coluna = um campo', count($colunas) === 5);
checa('chave primária identifica a linha sozinha', (int) $pdo->query('SELECT COUNT(DISTINCT id) FROM contatos')->fetchColumn() === 3);

fecharPratica();
