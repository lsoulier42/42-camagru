<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Container;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\MailerInterface;
use App\Core\Router;

// Conteneur : fabriques explicites (PDO, MailerInterface), le reste est autowiré.
$container = new Container();
$container->set(PDO::class, static fn (): PDO => Database::pdo());
$container->set(MailerInterface::class, static fn (): MailerInterface => new Mailer());

$router = new Router($container);

// Routes publiques
$router->get('/', 'Home@index');
$router->get('/index.php', 'Home@index'); // accès direct au front controller
$router->get('/gallery', 'Gallery@index');
$router->post('/gallery/like', 'Gallery@like');
$router->post('/gallery/comment', 'Gallery@comment');
$router->get('/image/{id}', 'Gallery@show'); // page de détail (partage social)

// Aucun 404 pour la favicon legacy (les navigateurs modernes utilisent /favicon.svg)
if (($_SERVER['REQUEST_URI'] ?? '') === '/favicon.ico') {
    http_response_code(204);
    exit;
}

// Authentification
$router->get('/register', 'Auth@showRegister');
$router->post('/register', 'Auth@register');
$router->get('/login', 'Auth@showLogin');
$router->post('/login', 'Auth@login');
$router->get('/confirm', 'Auth@confirm');
$router->get('/logout', 'Auth@logout');
$router->get('/forgot', 'Auth@showForgot');
$router->post('/forgot', 'Auth@forgot');
$router->get('/reset', 'Auth@showReset');
$router->post('/reset', 'Auth@reset');
$router->get('/profile', 'Auth@showProfile');
$router->post('/profile', 'Auth@updateProfile');

// Éditeur (connecté)
$router->get('/editor', 'Editor@index');
$router->post('/editor/capture', 'Editor@capture');
$router->post('/editor/gif', 'Editor@gif');
$router->post('/editor/upload', 'Editor@upload');
$router->post('/editor/delete', 'Editor@delete');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
