<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use App\Support\Database;
use App\Support\Sql;

/**
 * Runner de migração. Este arquivo é PHP porque orquestra (lê estado, decide
 * ordem, controla erro) — o SCHEMA em si vive em migrations/*.sql.
 */

Config::carregar();
$pdo = Database::conexao();
$raiz = dirname(__DIR__);

$pdo->exec(Sql::de($raiz . '/sql/migrations_table.sql'));

$comando = $argv[1] ?? 'up';
/** @var list<string> $aplicadas */
$aplicadas = $pdo->query('SELECT arquivo FROM migrations ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

// a migração é identificada pelo NOME BASE; .up.sql e .down.sql são o par dela
$migracoes = array_map(
    static fn (string $c): string => basename($c, '.up.sql'),
    glob($raiz . '/migrations/*.up.sql') ?: [],
);
sort($migracoes); // ordem determinística vem do prefixo YYYYMMDD_NNNN

$rodar = static function (string $nome, string $direcao) use ($pdo, $raiz): void {
    foreach (Sql::comandos("{$raiz}/migrations/{$nome}.{$direcao}.sql") as $comando) {
        $pdo->exec($comando);
    }
};

if ($comando === 'status') {
    foreach ($migracoes as $nome) {
        echo (in_array($nome, $aplicadas, true) ? '[x] ' : '[ ] ') . $nome . PHP_EOL;
    }

    exit(0);
}

if ($comando === 'up') {
    $total = 0;

    foreach ($migracoes as $nome) {
        if (in_array($nome, $aplicadas, true)) {
            continue;
        }

        // SEM beginTransaction() — e isto é deliberado.
        //
        // A aula envolve cada migração numa transação. Funciona em PostgreSQL, mas
        // NÃO no MySQL: DDL (CREATE/ALTER/DROP) provoca COMMIT IMPLÍCITO. A transação
        // morre no meio e o commit() seguinte estoura "There is no active transaction"
        // — com a tabela JÁ criada. Foi exatamente o que aconteceu ao rodar isto.
        //
        // Consequência: no MySQL, uma migração com duas alterações que falha na
        // segunda deixa a primeira aplicada. Daí a regra de UMA alteração estrutural
        // por migração, e backup antes de migração destrutiva (Módulo 7).
        try {
            $rodar($nome, 'up');
            $pdo->prepare('INSERT INTO migrations (arquivo) VALUES (:arquivo)')->execute(['arquivo' => $nome]);
            echo "Aplicada: {$nome}" . PHP_EOL;
            $total++;
        } catch (Throwable $e) {
            fwrite(STDERR, "Falhou em {$nome}: {$e->getMessage()}" . PHP_EOL);
            exit(1);
        }
    }

    echo $total === 0 ? 'Nada a aplicar.' . PHP_EOL : "{$total} migração(ões) aplicada(s)." . PHP_EOL;
    exit(0);
}

if ($comando === 'down') {
    $ultima = end($aplicadas);

    if ($ultima === false) {
        echo 'Nada para reverter.' . PHP_EOL;
        exit(0);
    }

    $rodar($ultima, 'down');
    $pdo->prepare('DELETE FROM migrations WHERE arquivo = :arquivo')->execute(['arquivo' => $ultima]);
    echo "Revertida: {$ultima}" . PHP_EOL;
    exit(0);
}

fwrite(STDERR, "Comando desconhecido: {$comando}. Use status, up ou down." . PHP_EOL);
exit(1);
