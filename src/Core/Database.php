<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Connexion PDO unique (lazy). Requêtes préparées uniquement (anti SQLi),
 * exceptions en mode ERRMODE_EXCEPTION, jamais affichées au client.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $host = Env::get('DB_HOST', 'db');
            $port = Env::get('DB_PORT', '3306');
            $name = Env::get('DB_NAME', 'camagru');
            $user = Env::get('DB_USER', 'camagru');
            $pass = Env::get('DB_PASS', 'camagru');

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
            } catch (PDOException $e) {
                error_log('[Camagru] Connexion base de données impossible : ' . $e->getMessage());
                throw $e;
            }
        }

        return self::$pdo;
    }
}
