<?php

use App\Core\Csrf;
use App\Core\Env;
use App\Core\ShareData;
use App\Core\View;
use App\Entities\GalleryImage;

/** @var GalleryImage $image Image détaillée (avec commentaires). */

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);

$currentUserId = $_SESSION['user_id'] ?? null;

// --- Partage social : citation = dernier commentaire (ou message par défaut) ---
$comments = $image->comments;
$appUrl = rtrim((string) Env::get('APP_URL', Env::DEFAULT_APP_URL), '/');
$share = ShareData::forImage($image, $appUrl);
?>
<section class="page-head">
    <h1>Image de <?= View::e($image->author) ?></h1>
    <p class="muted"><a href="/gallery">← Retour à la galerie</a></p>
    <meta name="csrf-token" content="<?= Csrf::token() ?>">
</section>

<article class="gallery-card gallery-card--detail" id="image-<?= $image->id ?>">
    <header class="gallery-card-head">
        <span class="gallery-author"><?= View::e($image->author) ?></span>
        <time class="gallery-date" datetime="<?= View::e($image->createdAt->format('Y-m-d H:i:s')) ?>">
            <?= View::e($image->createdAt->format('d/m/Y H:i')) ?>
        </time>
    </header>

    <img class="gallery-img gallery-img--detail" src="/uploads/<?= rawurlencode($image->filename) ?>"
         alt="Image de <?= View::e($image->author) ?>">

    <div class="gallery-actions">
        <?php if ($currentUserId !== null): ?>
            <form method="post" action="/gallery/like" class="inline-form js-like-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="image_id" value="<?= $image->id ?>">
                <input type="hidden" name="return_path" value="/image/<?= $image->id ?>">
                <button type="submit"
                        class="like-btn <?= $image->liked ? 'like-btn--active' : '' ?>"
                        aria-label="<?= $image->liked ? 'Retirer mon like' : 'J\'aime' ?>">
                    ♥ <?= $image->likesCount ?>
                </button>
            </form>
        <?php else: ?>
            <a class="like-btn" href="/login" title="Connectez-vous pour aimer">♥ <?= $image->likesCount ?></a>
        <?php endif; ?>
        <span class="gallery-comments-count">💬 <?= $image->commentsCount ?></span>

        <div class="gallery-share">
            <a class="share-link share-link--x" target="_blank" rel="noopener"
               href="<?= View::e($share->twitterUrl) ?>" title="Partager sur X (Twitter)">𝕏</a>
            <a class="share-link share-link--fb" target="_blank" rel="noopener"
               href="<?= View::e($share->facebookUrl) ?>" title="Partager sur Facebook">f</a>
        </div>
    </div>

    <div class="gallery-comments">
        <?php if ($comments === []): ?>
            <p class="muted">Aucun commentaire pour l'instant.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <p class="comment">
                    <strong><?= View::e($comment->author) ?></strong>
                    : <?= View::e($comment->content) ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($currentUserId !== null): ?>
        <form method="post" action="/gallery/comment" class="comment-form js-comment-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="image_id" value="<?= $image->id ?>">
            <input type="hidden" name="return_path" value="/image/<?= $image->id ?>">
            <label class="visually-hidden" for="comment-<?= $image->id ?>">Ajouter un commentaire</label>
            <textarea id="comment-<?= $image->id ?>" name="content" rows="3" maxlength="500"
                      placeholder="Ajouter un commentaire…" required></textarea>
            <button type="submit" class="btn btn--primary">Envoyer</button>
        </form>
    <?php else: ?>
        <p class="comment-login muted"><a href="/login">Connectez-vous</a> pour commenter ou aimer cette image.</p>
    <?php endif; ?>
</article>

<script src="/assets/js/gallery.js" defer></script>
<?php View::render('layout/footer'); ?>
