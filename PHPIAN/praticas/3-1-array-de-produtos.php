<?php

// PHPIAN · Módulo 3 · Aula 1 — Arrays indexados e associativos
// Prática: "Modele um array de 3 produtos, cada um com nome, preco e estoque.
// Percorra com foreach e imprima."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 3-1 — array de produtos');

secao('O array multidimensional');

$produtos = [
    ['nome' => 'Teclado mecânico', 'preco' => 349.90, 'estoque' => 12],
    ['nome' => 'Mouse sem fio',    'preco' =>  89.50, 'estoque' =>  0],
    ['nome' => 'Monitor 24"',      'preco' => 899.00, 'estoque' =>  3],
];

checa('são 3 produtos', count($produtos) === 3);
checa('índices numéricos de 0 a 2', array_keys($produtos) === [0, 1, 2]);
foreach ($produtos as $i => $p) {
    checa("produto {$i} tem as 3 chaves", array_keys($p) === ['nome', 'preco', 'estoque'], $p['nome']);
}

secao('O foreach que imprime');

$linhas = [];
foreach ($produtos as $p) {
    $linhas[] = sprintf(
        '%-18s R$ %9s  %s',
        $p['nome'],
        number_format($p['preco'], 2, ',', '.'),
        $p['estoque'] > 0 ? "{$p['estoque']} em estoque" : 'esgotado'
    );
}
foreach ($linhas as $l) {
    nota($l);
}
checa('imprimiu 3 linhas', count($linhas) === 3);
checa('o esgotado apareceu como esgotado', str_contains($linhas[1], 'esgotado'), 'estoque 0');

secao('"Arrays em PHP são mapas ordenados"');

// A ordem de inserção é preservada, e chaves numéricas e string convivem.
$misto = [];
$misto[] = 'primeiro';
$misto['nome'] = 'Carlos';
$misto[] = 'segundo';
checa('mistura índice e chave string', array_keys($misto) === [0, 'nome', 1]);
checa('a ordem de inserção é mantida', array_values($misto) === ['primeiro', 'Carlos', 'segundo']);

secao('Acesso, como no código da aula');

$cores = ['vermelho', 'verde', 'azul'];
$usuario = ['nome' => 'Carlos', 'email' => 'carlos@email.com', 'admin' => false];

checa('$cores[0] é o primeiro', $cores[0] === 'vermelho');
checa('$usuario["email"] pela chave', $usuario['email'] === 'carlos@email.com');
checa('admin é false, não ausente', $usuario['admin'] === false && array_key_exists('admin', $usuario),
    'isset() diria o contrário para null — array_key_exists é o certo aqui');

secao('Chave que não existe');

$erro = null;
set_error_handler(static function (int $n, string $msg) use (&$erro): bool { $erro = $msg; return true; });
$x = $usuario['telefone'];
restore_error_handler();
checa('chave ausente emite Warning e devolve null', $x === null && str_contains((string) $erro, 'Undefined array key'));
checa('com ?? não emite nada', ($usuario['telefone'] ?? 'sem telefone') === 'sem telefone');

secao('Totais, para provar que o modelo serve');

$valorEmEstoque = 0.0;
foreach ($produtos as $p) {
    $valorEmEstoque += $p['preco'] * $p['estoque'];
}
checa('valor total em estoque', abs($valorEmEstoque - (349.90 * 12 + 89.50 * 0 + 899.00 * 3)) < 0.001,
    'R$ ' . number_format($valorEmEstoque, 2, ',', '.'));
checa('1 produto esgotado', count(array_filter($produtos, static fn ($p) => $p['estoque'] === 0)) === 1);

fecharPratica();
