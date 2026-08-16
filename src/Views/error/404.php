<?php

use App\Core\View;

View::render('layout/header', ['pageTitle' => 'Page introuvable — Camagru']);
?>
<section class="page-head">
    <h1>Page introuvable</h1>
    <p class="muted">La page demandée n'existe pas ou a été déplacée.</p>
    <p><a class="btn btn--primary" href="/">Retour à l'accueil</a></p>
</section>

<?php View::render('layout/footer'); ?>
