<?php

use App\Core\Session;
use App\Core\View;

$flash = Session::flash('success');
$flashType = 'success';
if ($flash === null) {
    $flash = Session::flash('error');
    $flashType = 'error';
}

$currentUser = $_SESSION['username'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($pageTitle ?? 'Camagru') ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/">Camagru</a>
        <nav class="site-nav" aria-label="Navigation principale">
            <a href="/">Accueil</a>
            <a href="/gallery">Galerie</a>
            <?php if ($currentUser !== null): ?>
                <a href="/editor">Éditeur</a>
                <a href="/profile"><?= View::e($currentUser) ?></a>
                <a href="/logout">Déconnexion</a>
            <?php else: ?>
                <a href="/login">Connexion</a>
                <a href="/register">Inscription</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if ($flash !== null): ?>
    <div class="flash flash--<?= $flashType ?> container" role="status"><?= View::e($flash) ?></div>
<?php endif; ?>

<main class="container main">
