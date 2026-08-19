<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * État de santé de l'application (base de données), affiché en pied de
 * page en environnement de développement.
 */
final class HealthCheck
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Renvoie 'ok' si la base répond, 'error' sinon (jamais d'exception propagée). */
    public function databaseStatus(): string
    {
        try {
            $this->pdo->query('SELECT 1');

            return 'ok';
        } catch (PDOException $e) {
            error_log('[Camagru] Base de données injoignable : ' . $e->getMessage());

            return 'error';
        }
    }
}
