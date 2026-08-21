<?php

use App\Core\Csrf;
use App\Core\View;
use App\Entities\Image;

/** @var list<string> $overlays Noms des PNG superposables disponibles. */
/** @var list<Image> $myImages Images de l'utilisateur (vignettes). */

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);
?>
<section class="page-head">
    <h1>Éditeur</h1>
    <p class="muted">Capturez une photo (webcam ou upload), choisissez une image superposable,
        puis publiez-la dans la galerie. La superposition est composée côté serveur.</p>
</section>

<div class="editor-grid">
    <section class="editor-main">
        <div class="stage">
            <video id="webcam" autoplay playsinline muted hidden></video>
            <canvas id="capture-canvas" hidden></canvas>
            <img id="upload-preview" alt="Aperçu de l'image choisie" hidden>
            <img id="overlay-preview" class="overlay-preview"
                 alt="Aperçu de la superposition" hidden>
            <p id="cam-fallback" class="stage-fallback" hidden>
                Caméra indisponible — utilisez l'upload d'image ci-dessous.
            </p>
        </div>

        <div class="overlays">
            <p class="muted overlay-hint">1. Choisissez une image superposable&nbsp;:</p>
            <div class="overlay-list">
                <?php foreach ($overlays as $overlay): ?>
                    <button type="button" class="overlay-item" data-overlay="<?= View::e($overlay) ?>"
                            aria-label="Superposer <?= View::e($overlay) ?>">
                        <img src="/assets/overlays/<?= rawurlencode($overlay) ?>"
                             alt="<?= View::e($overlay) ?>" width="64" height="64">
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="editor-actions">
            <form id="capture-form" method="post" action="/editor/capture">
                <?= Csrf::field() ?>
                <input type="hidden" name="overlay" id="overlay-field" value="">
                <input type="hidden" name="image" id="image-data" value="">
                <button type="submit" id="capture-btn" class="btn btn--primary" disabled>
                    2. Capturer avec la webcam
                </button>
                <button type="submit" id="gif-btn" class="btn btn--gif" formaction="/editor/gif" disabled>
                    🎬 Exporter en GIF animé
                </button>
            </form>

            <form id="upload-form" method="post" action="/editor/upload" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <input type="hidden" name="overlay" id="upload-overlay-field" value="">
                <label class="btn file-btn" for="photo-input">
                    2. Choisir une image
                    <input type="file" id="photo-input" name="photo"
                           accept="image/png,image/jpeg,image/gif" hidden>
                </label>
                <button type="submit" id="upload-btn" class="btn btn--primary" disabled>
                    Publier cette image
                </button>
            </form>
        </div>
    </section>

    <aside class="editor-side">
        <h2>Mes photos (<?= count($myImages) ?>)</h2>
        <?php if ($myImages === []): ?>
            <div class="empty-state" style="padding: 2rem 0.5rem;">
                <span class="empty-state-icon" aria-hidden="true">🎨</span>
                <p>Capture ta première création&nbsp;!</p>
            </div>
        <?php else: ?>
            <div class="thumb-grid">
                <?php foreach ($myImages as $image): ?>
                    <figure class="thumb">
                        <img src="/uploads/<?= rawurlencode($image->filename) ?>"
                             alt="Photo du <?= View::e($image->createdAt->format('d/m/Y H:i')) ?>"
                             loading="lazy">
                        <figcaption>
                            <form method="post" action="/editor/delete">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="image_id" value="<?= $image->id ?>">
                                <button type="submit" class="btn btn--danger btn--small"
                                        data-confirm="Supprimer définitivement cette image ?">Supprimer</button>
                            </form>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>

<script src="/assets/js/editor.js"></script>
<?php View::render('layout/footer'); ?>
