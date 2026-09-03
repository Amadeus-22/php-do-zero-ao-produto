<?php

// PHPIAN · Módulo 6 · Aula 4 — INSERT, UPDATE e DELETE
// Prática: "Implemente formulário de criação e página de edição de contato."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 6-4 — criar e editar contato');

$pdo = bancoDaPratica();
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

secao('INSERT — a criação');

$criar = static function (PDO $pdo, array $d): int {
    $stmt = $pdo->prepare('INSERT INTO contatos (nome, email, telefone) VALUES (?, ?, ?)');
    $stmt->execute([trim($d['nome']), trim($d['email']), ($d['telefone'] ?? '') !== '' ? trim($d['telefone']) : null]);
    return (int) $pdo->lastInsertId();
};

$id = $criar($pdo, ['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com', 'telefone' => '21999990001']);
checa('lastInsertId devolve o id novo', $id === 1, "id={$id}");
$ana = $pdo->query("SELECT * FROM contatos WHERE id = {$id}")->fetch();
checa('gravou o nome', $ana['nome'] === 'Ana Souza');
checa('gravou o telefone', $ana['telefone'] === '21999990001');
checa('criado_em preenchido pelo banco', !empty($ana['criado_em']));

$semTelefone = $criar($pdo, ['nome' => 'Bruno Lima', 'email' => 'bruno@exemplo.com', 'telefone' => '']);
checa('telefone vazio vira NULL, não string vazia',
    $pdo->query("SELECT telefone FROM contatos WHERE id = {$semTelefone}")->fetchColumn() === null,
    'NULL diz "não informado"; "" diz "informou o vazio"');

$erro = null;
try {
    $criar($pdo, ['nome' => 'Outra Ana', 'email' => 'ana@exemplo.com', 'telefone' => null]);
} catch (PDOException $e) {
    $erro = $e->getCode();
}
checa('e-mail duplicado é recusado pelo banco', $erro === '23000', 'SQLSTATE 23000 = violação de integridade');
checa('continuam 2 contatos', (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn() === 2);

secao('UPDATE — a edição');

$editar = static function (PDO $pdo, int $id, array $d): int {
    $stmt = $pdo->prepare('UPDATE contatos SET nome = ?, email = ?, telefone = ? WHERE id = ?');
    $stmt->execute([trim($d['nome']), trim($d['email']), ($d['telefone'] ?? '') !== '' ? trim($d['telefone']) : null, $id]);
    return $stmt->rowCount();
};

$afetadas = $editar($pdo, $id, ['nome' => 'Ana Souza Lima', 'email' => 'ana@exemplo.com', 'telefone' => '21988887777']);
checa('rowCount diz 1 linha alterada', $afetadas === 1);
$depois = $pdo->query("SELECT * FROM contatos WHERE id = {$id}")->fetch();
checa('o nome mudou', $depois['nome'] === 'Ana Souza Lima');
checa('o telefone mudou', $depois['telefone'] === '21988887777');
checa('o id NÃO mudou', (int) $depois['id'] === $id);
checa('criado_em NÃO mudou', $depois['criado_em'] === $ana['criado_em'], 'só as colunas do SET foram tocadas');

checa('editar id inexistente afeta 0 linhas', $editar($pdo, 999999, ['nome' => 'X', 'email' => 'x@y.co', 'telefone' => null]) === 0,
    'é assim que a página de edição detecta "não encontrado"');

// Salvar sem mudar nada
$semMudanca = $editar($pdo, $id, ['nome' => 'Ana Souza Lima', 'email' => 'ana@exemplo.com', 'telefone' => '21988887777']);
checa('salvar sem alterar devolve 0, não erro', $semMudanca === 0,
    'rowCount conta linhas MUDADAS — não confunda com "falhou"');

secao('DELETE');

$excluir = static function (PDO $pdo, int $id): int {
    $stmt = $pdo->prepare('DELETE FROM contatos WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount();
};

checa('excluiu 1 linha', $excluir($pdo, $semTelefone) === 1);
checa('sobrou 1 contato', (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn() === 1);
checa('excluir de novo afeta 0', $excluir($pdo, $semTelefone) === 0);

secao('O WHERE que não pode faltar');

// DELETE sem WHERE apaga a tabela inteira. O guarda é o código exigir o id.
$excluirSeguro = static function (PDO $pdo, ?int $id): int {
    if ($id === null || $id <= 0) {
        throw new InvalidArgumentException('id obrigatório');
    }
    $stmt = $pdo->prepare('DELETE FROM contatos WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount();
};
checaExcecao('excluir sem id é recusado', \InvalidArgumentException::class, static fn () => $excluirSeguro($pdo, null));
checaExcecao('id 0 (o que (int)"" produz) é recusado', \InvalidArgumentException::class, static fn () => $excluirSeguro($pdo, 0));
checa('o contato restante sobreviveu', (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn() === 1);

secao('PRG: o redirect depois do POST');

// A aula fecha lembrando o PRG da 4-5. Aqui o teste é o mesmo raciocínio,
// aplicado ao INSERT: depois de gravar, responde 302 e não repete a gravação.
$antes = (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn();
$processar = static function (PDO $pdo, string $metodo, array $post) use ($criar): array {
    if ($metodo !== 'POST') {
        return ['status' => 200, 'local' => null];
    }
    $criar($pdo, $post);
    return ['status' => 302, 'local' => 'contatos.php'];
};

$r = $processar($pdo, 'POST', ['nome' => 'Carla Andrade', 'email' => 'carla@exemplo.com', 'telefone' => null]);
checa('POST responde 302', $r['status'] === 302);
checa('com Location', $r['local'] === 'contatos.php');
checa('gravou 1', (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn() === $antes + 1);

$g = $processar($pdo, 'GET', []);
checa('o GET seguinte NÃO grava de novo', (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn() === $antes + 1,
    'é o que o F5 faria sem PRG');
checa('e responde 200', $g['status'] === 200);

fecharPratica();
