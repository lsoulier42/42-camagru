<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\HealthCheck;
use App\Core\View;

final class HomeController
{
    public function __construct(private readonly HealthCheck $health)
    {
    }

    public function index(): void
    {
        // En dev uniquement : indicateur d'état de la base dans le footer.
        $dbStatus = null;
        if (Env::get('APP_ENV', 'dev') === 'dev') {
            $dbStatus = $this->health->databaseStatus();
        }

        View::render('home/index', [
            'pageTitle' => 'Camagru — Capturez, superposez, partagez',
            'dbStatus' => $dbStatus,
        ]);
    }
}
