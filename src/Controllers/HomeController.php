<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Core\View;
use PDOException;

final class HomeController
{
    public function index(): void
    {
        // En dev uniquement : indicateur d'état de la base dans le footer.
        $dbStatus = null;
        if (Env::get('APP_ENV', 'dev') === 'dev') {
            $dbStatus = $this->databaseStatus();
        }

        View::render('home/index', [
            'pageTitle' => 'Camagru — Capturez, superposez, partagez',
            'dbStatus' => $dbStatus,
        ]);
    }

    private function databaseStatus(): string
    {
        try {
            Database::pdo()->query('SELECT 1');
            return 'ok';
        } catch (PDOException $e) {
            error_log('[Camagru] Base de données injoignable : ' . $e->getMessage());
            return 'error';
        }
    }
}
