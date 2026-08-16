<?php

use App\Core\Csrf;
use App\Core\Env;
use App\Core\View;

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);

$image = $image ?? [];
$currentUserId = $_SESSION['user_id'] ?? null;

// --- Partage social : citation = dernier commentaire (ou message par défaut) ---
$appUrl = rtrim((string) Env::get('APP_URL', 'http://localhost:8080'), '/');
$imageUrl = $appUrl . '/image/' . (int) $image['id'];
$quote = 'Découvrez cette image sur Camagru !';
if (!empty($image['comments'])) {
    $last = end($image['comments']);
    $quote = trim((string) $last['content']);
    if (mb_strlen($quote) > 120) {
        $quote = mb_substr($quote, 0, 117) . '…';
    }
}
$shareTwitter = 'https://twitter.com/intent/tweet?url=' . rawurlencode($imageUrl) . '&text=' . rawurlencode($quote);
$shareFacebook = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($imageUrl);
?>
<section class="page-head">
    <h1>Image de <?= View::e((string) $image['author']) ?></h1>
    <p class="muted"><a href="/gallery">← Retour à la galerie</a></p>
    <meta name="csrf-token" content="<?= Csrf::token() ?>">
</section>

<article class="gallery-card gallery-card--detail" id="image-<?= (int) $image['id'] ?>">
    <header class="gallery-card-head">
        <span class="gallery-author"><?= View::e((string) $image['author']) ?></span>
        <time class="gallery-date" datetime="<?= View::e((string) $image['created_at']) ?>">
            <?= View::e(date('d/m/Y H:i', strtotime((string) $image['created_at']))) ?>
        </time>
    </header>

    <img class="gallery-img gallery-img--detail" src="/uploads/<?= rawurlencode((string) $image['filename']) ?>"
         alt="Image de <?= View::e((string) $image['author']) ?>">

    <div class="gallery-actions">
        <?php if ($currentUserId !== null): ?>
            <form method="post" action="/gallery/like" class="inline-form js-like-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                <input type="hidden" name="return_path" value="/image/<?= (int) $image['id'] ?>">
                <button type="submit"
                        class="like-btn <?= $image['liked'] ? 'like-btn--active' : '' ?>"
                        aria-label="<?= $image['liked'] ? 'Retirer mon like' : 'J\'aime' ?>">
                    ♥ <?= (int) $image['likes_count'] ?>
                </button>
            </form>
        <?php else: ?>
            <a class="like-btn" href="/login" title="Connectez-vous pour aimer">♥ <?= (int) $image['likes_count'] ?></a>
        <?php endif; ?>
        <span class="gallery-comments-count">💬 <?= (int) $image['comments_count'] ?></span>

        <div class="gallery-share">
            <a class="share-link share-link--x" target="_blank" rel="noopener"
               href="<?= View::e($shareTwitter) ?>" title="Partager sur X (Twitter)">𝕏</a>
            <a class="share-link share-link--fb" target="_blank" rel="noopener"
               href="<?= View::e($shareFacebook) ?>" title="Partager sur Facebook">f</a>
        </div>
    </div>

    <div class="gallery-comments">
        <?php if ($image['comments'] === []): ?>
            <p class="muted">Aucun commentaire pour l'instant.</p>
        <?php else: ?>
            <?php foreach ($image['comments'] as $comment): ?>
                <p class="comment">
                    <strong><?= View::e((string) $comment['author']) ?></strong>
                    : <?= View::e((string) $comment['content']) ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($currentUserId !== null): ?>
        <form method="post" action="/gallery/comment" class="comment-form js-comment-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
            <input type="hidden" name="return_path" value="/image/<?= (int) $image['id'] ?>">
            <label class="visually-hidden" for="comment-<?= (int) $image['id'] ?>">Ajouter un commentaire</label>
            <textarea id="comment-<?= (int) $image['id'] ?>" name="content" rows="3" maxlength="500"
                      placeholder="Ajouter un commentaire…" required></textarea>
            <button type="submit" class="btn btn--primary">Envoyer</button>
        </form>
    <?php else: ?>
        <p class="comment-login muted"><a href="/login">Connectez-vous</a> pour commenter ou aimer cette image.</p>
    <?php endif; ?>
</article>

<script src="/assets/js/gallery.js" defer></script>
<?php View::render('layout/footer'); ?>
