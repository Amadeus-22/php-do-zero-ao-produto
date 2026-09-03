<?php

declare(strict_types=1);

namespace App\Support;

use App\Config\Config;
use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function conexao(): PDO
    {
        return self::$pdo ??= new PDO(
            sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                Config::string('DB_DRIVER', 'mysql'),
                Config::string('DB_HOST'),
                Config::int('DB_PORT', 3306),
                Config::string('DB_DATABASE'),
            ),
            Config::string('DB_USERNAME'),
            Config::string('DB_PASSWORD', ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // prepared statement de verdade, no servidor
            ],
        );
    }

    public static function usar(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
