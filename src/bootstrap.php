<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Autoloader maison (PSR-4 minimal) — zéro dépendance Composer.
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = APP_ROOT . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Configuration depuis .env (fichier local, hors git).
\App\Core\Env::load(APP_ROOT . '/.env');

// Session sécurisée (HttpOnly, SameSite=Lax).
\App\Core\Session::start();
