<?php

// PHPIAN · Módulo 8 · Aula 3 — CRUD completo
// Prática: "Finalize o CRUD e teste com 2 usuários diferentes."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 8-3 — CRUD com ownership, testado com 2 usuários');

$pdo = bancoDaPratica();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['contato_tag', 'tags', 'contatos', 'users'] as $t) {
    $pdo->exec("DROP TABLE IF EXISTS {$t}");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
$pdo->exec('CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL UNIQUE, senha_hash VARCHAR(255) NOT NULL)');
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

$ins = $pdo->prepare('INSERT INTO users (nome, email, senha_hash) VALUES (?, ?, ?)');
$ins->execute(['Ana', 'ana@exemplo.com', password_hash('x', PASSWORD_DEFAULT)]);
$ana = (int) $pdo->lastInsertId();
$ins->execute(['Bruno', 'bruno@exemplo.com', password_hash('x', PASSWORD_DEFAULT)]);
$bruno = (int) $pdo->lastInsertId();

secao('O CRUD — toda query filtra por user_id');

$criar = static function (PDO $pdo, int $dono, array $d): int {
    $stmt = $pdo->prepare('INSERT INTO contatos (user_id, nome, email, telefone, notas) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$dono, trim($d['nome']), $d['email'] ?? null, $d['telefone'] ?? null, $d['notas'] ?? null]);
    return (int) $pdo->lastInsertId();
};

$listar = static function (PDO $pdo, int $dono, string $busca = ''): array {
    if ($busca === '') {
        $stmt = $pdo->prepare('SELECT * FROM contatos WHERE user_id = ? ORDER BY nome');
        $stmt->execute([$dono]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM contatos WHERE user_id = ? AND (nome LIKE ? OR email LIKE ?) ORDER BY nome');
        $stmt->execute([$dono, "%{$busca}%", "%{$busca}%"]);
    }
    return $stmt->fetchAll();
};

$ver = static function (PDO $pdo, int $dono, int $id): array|false {
    // O user_id no WHERE é a autorização. Sem ele, trocar o id na URL bastaria.
    $stmt = $pdo->prepare('SELECT * FROM contatos WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $dono]);
    return $stmt->fetch();
};

$editar = static function (PDO $pdo, int $dono, int $id, array $d): int {
    $stmt = $pdo->prepare('UPDATE contatos SET nome = ?, email = ?, telefone = ?, notas = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([trim($d['nome']), $d['email'] ?? null, $d['telefone'] ?? null, $d['notas'] ?? null, $id, $dono]);
    return $stmt->rowCount();
};

$excluir = static function (PDO $pdo, int $dono, int $id): int {
    $stmt = $pdo->prepare('DELETE FROM contatos WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $dono]);
    return $stmt->rowCount();
};

secao('Create');

$c1 = $criar($pdo, $ana, ['nome' => 'Cliente Alfa', 'email' => 'alfa@cliente.com', 'telefone' => '21999990001', 'notas' => 'Reunião em outubro']);
$c2 = $criar($pdo, $ana, ['nome' => 'Cliente Beta', 'email' => 'beta@cliente.com']);
$c3 = $criar($pdo, $bruno, ['nome' => 'Cliente Gama', 'email' => 'gama@cliente.com']);

checa('Ana criou 2 contatos', count($listar($pdo, $ana)) === 2);
checa('Bruno criou 1', count($listar($pdo, $bruno)) === 1);
checa('as notas foram gravadas', $ver($pdo, $ana, $c1)['notas'] === 'Reunião em outubro');
checa('campos opcionais ficam NULL', $ver($pdo, $ana, $c2)['telefone'] === null);

secao('Read — cada um vê só o seu');

checa('Ana vê Alfa e Beta', array_column($listar($pdo, $ana), 'nome') === ['Cliente Alfa', 'Cliente Beta']);
checa('Bruno vê só Gama', array_column($listar($pdo, $bruno), 'nome') === ['Cliente Gama']);
checa('a lista da Ana não tem nada do Bruno', !in_array('Cliente Gama', array_column($listar($pdo, $ana), 'nome'), true));

secao('Busca — também filtrada por dono');

checa('Ana busca "Cliente" e acha 2', count($listar($pdo, $ana, 'Cliente')) === 2);
checa('Ana busca "Gama" e acha 0', count($listar($pdo, $ana, 'Gama')) === 0, 'existe, mas é do Bruno');
checa('Bruno busca "Gama" e acha 1', count($listar($pdo, $bruno, 'Gama')) === 1);
checa('busca por e-mail funciona', count($listar($pdo, $ana, 'alfa@')) === 1);
checa("busca com aspa não quebra", count($listar($pdo, $ana, "' OR '1'='1")) === 0, 'prepared statement');

secao('O callout: ownership — o teste com 2 usuários');

// É aqui que a prática pede os dois usuários: provar que um não alcança o outro.
checa('Bruno NÃO consegue LER o contato da Ana', $ver($pdo, $bruno, $c1) === false,
    'trocar o id na URL não basta');
checa('Bruno NÃO consegue EDITAR o contato da Ana', $editar($pdo, $bruno, $c1, ['nome' => 'Invadido']) === 0);
checa('o contato da Ana ficou intacto', $ver($pdo, $ana, $c1)['nome'] === 'Cliente Alfa');
checa('Bruno NÃO consegue EXCLUIR o contato da Ana', $excluir($pdo, $bruno, $c1) === 0);
checa('o contato continua existindo', $ver($pdo, $ana, $c1) !== false);
checa('e a Ana também não alcança o do Bruno', $ver($pdo, $ana, $c3) === false, 'a barreira vale nos dois sentidos');

secao('Update e Delete do próprio dono');

checa('Ana edita o que é dela', $editar($pdo, $ana, $c1, ['nome' => 'Cliente Alfa Ltda', 'email' => 'alfa@cliente.com', 'telefone' => '21988887777', 'notas' => 'Fechado']) === 1);
$editado = $ver($pdo, $ana, $c1);
checa('o nome mudou', $editado['nome'] === 'Cliente Alfa Ltda');
checa('o telefone mudou', $editado['telefone'] === '21988887777');
checa('o user_id NÃO mudou', (int) $editado['user_id'] === $ana, 'o SET não toca no dono');

checa('Ana exclui o que é dela', $excluir($pdo, $ana, $c2) === 1);
checa('sobrou 1 contato para a Ana', count($listar($pdo, $ana)) === 1);
checa('o do Bruno não foi afetado', count($listar($pdo, $bruno)) === 1);

secao('O que aconteceria SEM o user_id no WHERE');

// A versão ingênua — a que a maioria escreve primeiro.
$verSemDono = static function (PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare('SELECT * FROM contatos WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
};
checa('sem ownership, Bruno leria o contato da Ana', $verSemDono($pdo, $c1) !== false,
    'é a falha que o callout da aula evita');
nota('a diferença entre as duas versões é uma condição no WHERE — e é a diferença entre vazar ou não');

secao('Escape na listagem');

$hostil = $criar($pdo, $ana, ['nome' => '<script>alert(document.cookie)</script>', 'email' => 'x@y.co']);
$linha = $ver($pdo, $ana, $hostil);
checa('o banco guarda o valor cru', str_contains($linha['nome'], '<script>'), 'escapar é na saída');
$html = '<td>' . htmlspecialchars($linha['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
checa('a tabela HTML sai escapada', !str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;'));

fecharPratica();
