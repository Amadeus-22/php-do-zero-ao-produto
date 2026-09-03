<?php

// PHPIAN · Módulo 6 · Aula 3 — SELECT e prepared statements
// Prática: "Liste todos os contatos em uma tabela HTML. Crie também busca por
// nome com LIKE ?."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 6-3 — listar e buscar contatos');

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

$semente = [
    ['Ana Souza', 'ana@exemplo.com', '21999990001'],
    ['Bruno Lima', 'bruno@exemplo.com', null],
    ['Carla Andrade', 'carla@exemplo.com', '21999990003'],
    ['Ana Paula Reis', 'anapaula@exemplo.com', '21999990004'],
    ['<script>alert(1)</script>', 'hostil@exemplo.com', null],
];
$ins = $pdo->prepare('INSERT INTO contatos (nome, email, telefone) VALUES (?, ?, ?)');
foreach ($semente as $c) {
    $ins->execute($c);
}

secao('Listar todos');

$listar = static function (PDO $pdo): array {
    return $pdo->query('SELECT id, nome, email, telefone FROM contatos ORDER BY nome')->fetchAll();
};

$todos = $listar($pdo);
checa('trouxe os 5 contatos', count($todos) === 5);
checa('ordenado por nome', $todos[0]['nome'] === '<script>alert(1)</script>' && $todos[1]['nome'] === 'Ana Paula Reis',
    'o "<" ordena antes das letras');
checa('id volta como int', is_int($todos[0]['id']), 'EMULATE_PREPARES=false');

secao('A tabela HTML');

$tabela = static function (array $contatos): string {
    $esc = static fn (?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $html = "<table>\n  <thead><tr><th>Nome</th><th>E-mail</th><th>Telefone</th></tr></thead>\n  <tbody>\n";
    foreach ($contatos as $c) {
        $html .= sprintf(
            "    <tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",
            $esc($c['nome']),
            $esc($c['email']),
            $esc($c['telefone'] ?? '—')
        );
    }
    return $html . "  </tbody>\n</table>\n";
};

$html = $tabela($todos);
checa('tem <table> e <thead>', str_contains($html, '<table>') && str_contains($html, '<thead>'));
checa('tem 5 linhas no corpo', substr_count($html, '<tr>') === 6, '1 do cabeçalho + 5');
checa('mostra "—" quando o telefone é NULL', str_contains($html, '<td>—</td>'));
checa('o nome hostil saiu ESCAPADO', str_contains($html, '&lt;script&gt;') && !str_contains($html, '<script>alert'),
    'a lição da 4-2 aplicada na listagem');

secao('Busca com LIKE ?');

$buscar = static function (PDO $pdo, string $termo): array {
    // O % entra no VALOR, não no SQL. Assim o termo continua sendo dado.
    $stmt = $pdo->prepare('SELECT id, nome, email FROM contatos WHERE nome LIKE ? OR email LIKE ? ORDER BY nome');
    $curinga = '%' . $termo . '%';
    $stmt->execute([$curinga, $curinga]);
    return $stmt->fetchAll();
};

$ana = $buscar($pdo, 'Ana');
checa('busca "Ana" traz 2', count($ana) === 2, implode(', ', array_column($ana, 'nome')));
checa('achou Ana Souza e Ana Paula', array_column($ana, 'nome') === ['Ana Paula Reis', 'Ana Souza']);
checa('busca é case-insensitive', count($buscar($pdo, 'ana')) === count($buscar($pdo, 'ANA')), 'collation utf8mb4_..._ci');
checa('busca no meio da palavra', count($buscar($pdo, 'ouza')) === 1, '%termo% pega Souza');
checa('busca por e-mail também', count($buscar($pdo, 'carla@')) === 1);
checa('termo inexistente traz 0', $buscar($pdo, 'zzzzz') === []);
checa('termo vazio traz todos', count($buscar($pdo, '')) === 5, '%%; casa com tudo');

secao('SQL injection — o callout da aula, testado');

$antes = (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn();

foreach ([
    "' OR '1'='1",
    "'; DROP TABLE contatos; --",
    "%' UNION SELECT 1,2,3 -- ",
    "\\' OR 1=1 #",
] as $ataque) {
    $r = $buscar($pdo, $ataque);
    checa(sprintf('%-28s -> %d resultado(s)', mb_substr($ataque, 0, 28), count($r)), count($r) === 0,
        'tratado como texto, não como SQL');
}
checa('a tabela continua existindo', (bool) $pdo->query("SHOW TABLES LIKE 'contatos'")->fetch());
checa('nenhuma linha foi perdida', (int) $pdo->query('SELECT COUNT(*) FROM contatos')->fetchColumn() === $antes);

secao('O que aconteceria concatenando (o "antes" da aula)');

// Sem executar: só montamos a string para ver o comando que o atacante ganharia.
$montar = static fn (string $termo): string => "SELECT * FROM contatos WHERE nome LIKE '%{$termo}%'";
$queryAtacada = $montar("x'; DROP TABLE contatos; --");
nota($queryAtacada);
checa('a entrada fecha a aspa e emenda um DROP', str_contains($queryAtacada, 'DROP TABLE'),
    'deixou de ser um SELECT só');

secao('Busca por id — prepared com inteiro');

$porId = $pdo->prepare('SELECT * FROM contatos WHERE id = ?');
$porId->execute([(int) $todos[1]['id']]);
checa('acha pelo id', ($porId->fetch()['nome'] ?? '') === 'Ana Paula Reis');
$porId->execute([999999]);
checa('id inexistente devolve false', $porId->fetch() === false, 'não é array vazio — é false');

fecharPratica();
