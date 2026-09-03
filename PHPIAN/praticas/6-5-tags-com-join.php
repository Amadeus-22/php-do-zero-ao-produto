<?php

// PHPIAN · Módulo 6 · Aula 5 — Joins e relações simples
// Prática: "Crie 2 tags, associe a um contato e liste as tags desse contato com JOIN."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 6-5 — tags com JOIN');

$pdo = bancoDaPratica();
$pdo->exec('DROP TABLE IF EXISTS contato_tag');
$pdo->exec('DROP TABLE IF EXISTS tags');
$pdo->exec('DROP TABLE IF EXISTS contatos');

$pdo->exec(<<<'SQL'
CREATE TABLE contatos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE
)
SQL);
$pdo->exec(<<<'SQL'
CREATE TABLE tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(60) NOT NULL UNIQUE
)
SQL);
// A tabela de junção da aula, com as chaves estrangeiras que ela não escreveu.
$pdo->exec(<<<'SQL'
CREATE TABLE contato_tag (
  contato_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (contato_id, tag_id),
  FOREIGN KEY (contato_id) REFERENCES contatos(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
)
SQL);

secao('Os dados');

$pdo->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)')->execute(['Ana Souza', 'ana@exemplo.com']);
$ana = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)')->execute(['Bruno Lima', 'bruno@exemplo.com']);
$bruno = (int) $pdo->lastInsertId();

$insTag = $pdo->prepare('INSERT INTO tags (nome) VALUES (?)');
$tag = [];
foreach (['cliente', 'vip', 'prospect'] as $nome) {
    $insTag->execute([$nome]);
    $tag[$nome] = (int) $pdo->lastInsertId();
}
checa('2 contatos criados', (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn() === 2);
checa('3 tags criadas', (int) $pdo->query('SELECT COUNT(*) FROM tags')->fetchColumn() === 3);

secao('Associar as 2 tags ao contato');

$associar = $pdo->prepare('INSERT INTO contato_tag (contato_id, tag_id) VALUES (?, ?)');
$associar->execute([$ana, $tag['cliente']]);
$associar->execute([$ana, $tag['vip']]);
$associar->execute([$bruno, $tag['prospect']]);

checa('3 associações', (int) $pdo->query('SELECT COUNT(*) FROM contato_tag')->fetchColumn() === 3);

secao('O JOIN da aula');

$tagsDe = static function (PDO $pdo, int $contatoId): array {
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT c.nome, t.nome AS tag
        FROM contatos c
        JOIN contato_tag ct ON ct.contato_id = c.id
        JOIN tags t ON t.id = ct.tag_id
        WHERE c.id = ?
        ORDER BY t.nome
    SQL);
    $stmt->execute([$contatoId]);
    return $stmt->fetchAll();
};

$daAna = $tagsDe($pdo, $ana);
foreach ($daAna as $l) {
    nota("{$l['nome']} -> {$l['tag']}");
}
checa('a Ana tem 2 tags', count($daAna) === 2);
checa('são cliente e vip', array_column($daAna, 'tag') === ['cliente', 'vip']);
checa('o alias "AS tag" evita colisão de nome', array_keys($daAna[0]) === ['nome', 'tag'],
    'as duas tabelas têm coluna "nome"');
checa('o Bruno tem 1 tag', count($tagsDe($pdo, $bruno)) === 1);

secao('JOIN x LEFT JOIN — quem some da lista');

$pdo->prepare('INSERT INTO contatos (nome, email) VALUES (?, ?)')->execute(['Célia Reis', 'celia@exemplo.com']);
$celia = (int) $pdo->lastInsertId();

checa('Célia (sem tag) NÃO aparece no JOIN', $tagsDe($pdo, $celia) === [],
    'JOIN só devolve quem tem correspondência dos dois lados');

$comLeft = $pdo->prepare(<<<'SQL'
    SELECT c.nome, t.nome AS tag
    FROM contatos c
    LEFT JOIN contato_tag ct ON ct.contato_id = c.id
    LEFT JOIN tags t ON t.id = ct.tag_id
    WHERE c.id = ?
SQL);
$comLeft->execute([$celia]);
$linha = $comLeft->fetch();
checa('com LEFT JOIN ela aparece', $linha !== false);
checa('e a tag vem NULL', $linha['tag'] === null, 'é o que uma listagem de contatos quer');

secao('A chave primária composta');

$erro = null;
try {
    $associar->execute([$ana, $tag['cliente']]);
} catch (PDOException $e) {
    $erro = $e->getCode();
}
checa('associar a mesma tag duas vezes é bloqueado', $erro === '23000',
    'PRIMARY KEY (contato_id, tag_id) impede duplicata');

secao('As chaves estrangeiras');

$erroFk = null;
try {
    $associar->execute([999999, $tag['vip']]);
} catch (PDOException $e) {
    $erroFk = $e->getMessage();
}
checa('não dá para associar contato inexistente', str_contains((string) $erroFk, 'foreign key'),
    'a aula não escreveu as FKs — sem elas isto passaria e criaria lixo');

$pdo->prepare('DELETE FROM contatos WHERE id = ?')->execute([$ana]);
checa('ON DELETE CASCADE limpou as associações da Ana',
    (int) $pdo->query("SELECT COUNT(*) FROM contato_tag WHERE contato_id = {$ana}")->fetchColumn() === 0);
checa('as tags em si continuam', (int) $pdo->query('SELECT COUNT(*) FROM tags')->fetchColumn() === 3,
    'apagar o contato não apaga a tag, que é de todos');

secao('Todos os contatos com suas tags, em uma query');

$listagem = $pdo->query(<<<'SQL'
    SELECT c.nome, COALESCE(GROUP_CONCAT(t.nome ORDER BY t.nome SEPARATOR ', '), '—') AS tags
    FROM contatos c
    LEFT JOIN contato_tag ct ON ct.contato_id = c.id
    LEFT JOIN tags t ON t.id = ct.tag_id
    GROUP BY c.id, c.nome
    ORDER BY c.nome
SQL)->fetchAll();

foreach ($listagem as $l) {
    nota(sprintf('%-14s %s', $l['nome'], $l['tags']));
}
checa('lista os 2 contatos restantes', count($listagem) === 2);
checa('Bruno com prospect', ($listagem[0]['tags'] ?? '') === 'prospect');
checa('Célia com "—"', ($listagem[1]['tags'] ?? '') === '—');
nota('uma query só — sem isto seriam N+1 consultas, uma por contato');

fecharPratica();
