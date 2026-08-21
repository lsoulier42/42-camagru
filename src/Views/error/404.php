<?php

use App\Core\View;

View::render('layout/header', ['pageTitle' => 'Page introuvable — Camagru']);
?>
<div class="empty-state">
    <span class="empty-state-icon" aria-hidden="true">🔍</span>
    <h1>Page introuvable</h1>
    <p>La page demandée n'existe pas ou a été déplacée.</p>
    <a class="btn btn--primary" href="/">Retour à l'accueil</a>
</div>

<?php View::render('layout/footer'); ?>