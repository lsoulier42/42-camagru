<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Router;

$router = new Router();

// Routes publiques
$router->get('/', 'Home@index');
$router->get('/index.php', 'Home@index'); // accès direct au front controller
$router->get('/gallery', 'Gallery@index');
$router->post('/gallery/like', 'Gallery@like');
$router->post('/gallery/comment', 'Gallery@comment');

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
$router->post('/editor/upload', 'Editor@upload');
$router->post('/editor/delete', 'Editor@delete');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
