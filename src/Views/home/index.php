<?php

use App\Core\View;

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);
?>
<section class="hero">
    <h1>Capturez. Superposez. Partagez.</h1>
    <p class="hero-subtitle">
        Camagru, l'application façon Instagram&nbsp;: photo via webcam ou upload,
        superposition d'images à canal alpha, galerie publique, likes et commentaires.
    </p>
    <div class="hero-actions">
        <a class="btn btn--primary" href="/gallery">Voir la galerie</a>
    </div>
</section>

<section class="features">
    <div class="cards">
        <article class="card">
            <h2>📷 Éditeur photo</h2>
            <p>Capture webcam ou upload de fichier, puis superposition de PNG à canal alpha.
                Le rendu final est composé <strong>côté serveur</strong> (GD).</p>
        </article>
        <article class="card">
            <h2>🖼️ Galerie publique</h2>
            <p>Toutes les créations de tous les utilisateurs, triées par date de création
                et consultables sans compte.</p>
        </article>
        <article class="card">
            <h2>❤️ Likes &amp; commentaires</h2>
            <p>Connectez-vous pour réagir aux images, et recevez un email quand quelqu'un
                commente la vôtre.</p>
        </article>
    </div>
</section>

<?php View::render('layout/footer', ['dbStatus' => $dbStatus ?? null]); ?>
