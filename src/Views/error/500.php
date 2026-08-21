<?php

use App\Core\View;

View::render('layout/header', ['pageTitle' => 'Erreur interne — Camagru']);
?>
<div class="empty-state">
    <span class="empty-state-icon" aria-hidden="true">⚠️</span>
    <h1>Erreur interne</h1>
    <p>Une erreur est survenue. L'équipe technique a été prévenue (regardez les logs).</p>
    <a class="btn btn--primary" href="/">Retour à l'accueil</a>
</div>

<?php View::render('layout/footer'); ?>