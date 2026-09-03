<?php
declare(strict_types=1);

/** Conexão PDO única da aplicação. Nenhum "new PDO" fora daqui. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        (string) config('db.host', '127.0.0.1'),
        (int) config('db.port', 3306),
        (string) config('db.name', 'mini_crm'),
        (string) config('db.charset', 'utf8mb4')
    );

    try {
        $pdo = new PDO($dsn, (string) config('db.user'), (string) config('db.pass'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Prepares reais no servidor: sem interpolação de string em lugar nenhum.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $erro) {
        http_response_code(500);
        exit(config('debug')
            ? 'Erro de conexão com o banco: ' . e($erro->getMessage())
            : 'Erro ao conectar ao banco de dados.');
    }

    return $pdo;
}
