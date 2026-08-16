<?php

use App\Core\View;

View::render('layout/header', ['pageTitle' => 'Erreur interne — Camagru']);
?>
<section class="page-head">
    <h1>Erreur interne</h1>
    <p class="muted">Une erreur est survenue. L'équipe technique a été prévenue (regardez les logs).</p>
    <p><a class="btn btn--primary" href="/">Retour à l'accueil</a></p>
</section>

<?php View::render('layout/footer'); ?>
