<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base des tests d'intégration : chaque test part d'une base propre
 * (camagru_test, créée par tests/bootstrap.php).
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $pdo = Database::pdo();
        // Ordre des dépendances (FK) : enfants avant parents.
        foreach (['likes', 'comments', 'images', 'tokens', 'users'] as $table) {
            $pdo->exec('DELETE FROM ' . $table);
        }
    }
}
