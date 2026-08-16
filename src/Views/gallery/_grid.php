<?php

use App\Core\Csrf;
use App\Core\Env;
use App\Core\View;

$currentUserId = $_SESSION['user_id'] ?? null;
$appUrl = rtrim((string) Env::get('APP_URL', 'http://localhost:8080'), '/');
?>
<p class="muted" id="gallery-count">
    <?= (int) $total ?> image<?= $total > 1 ? 's' : '' ?> — page <?= (int) $page ?> / <?= (int) $totalPages ?>
</p>

<?php if ($images === []): ?>
    <p class="muted">Aucune image pour l'instant. Revenez bientôt !</p>
<?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($images as $image): ?>
            <article class="gallery-card" id="image-<?= (int) $image['id'] ?>">
                <header class="gallery-card-head">
                    <span class="gallery-author"><?= View::e($image['author']) ?></span>
                    <time class="gallery-date" datetime="<?= View::e($image['created_at']) ?>">
                        <?= View::e(date('d/m/Y H:i', strtotime((string) $image['created_at']))) ?>
                    </time>
                </header>

                <?php
                // Citation pour le partage : dernier commentaire (ou message par défaut).
                $quote = 'Découvrez cette image sur Camagru !';
                if ($image['comments'] !== []) {
                    $last = end($image['comments']);
                    $quote = trim((string) $last['content']);
                    if (mb_strlen($quote) > 120) {
                        $quote = mb_substr($quote, 0, 117) . '…';
                    }
                }
                $imageUrl = $appUrl . '/image/' . (int) $image['id'];
                $shareTwitter = 'https://twitter.com/intent/tweet?url=' . rawurlencode($imageUrl) . '&text=' . rawurlencode($quote);
                $shareFacebook = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($imageUrl);
                ?>
                <a class="gallery-img-link" href="/image/<?= (int) $image['id'] ?>">
                    <img class="gallery-img" src="/uploads/<?= rawurlencode((string) $image['filename']) ?>"
                         alt="Image de <?= View::e($image['author']) ?>" loading="lazy">
                </a>

                <div class="gallery-actions">
                    <?php if ($currentUserId !== null): ?>
                        <form method="post" action="/gallery/like" class="inline-form js-like-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                            <input type="hidden" name="page" value="<?= (int) $page ?>">
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
                    <?php foreach ($image['comments'] as $comment): ?>
                        <p class="comment">
                            <strong><?= View::e($comment['author']) ?></strong>
                            : <?= View::e($comment['content']) ?>
                        </p>
                    <?php endforeach; ?>
                </div>

                <?php if ($currentUserId !== null): ?>
                    <form method="post" action="/gallery/comment" class="comment-form js-comment-form">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                        <input type="hidden" name="page" value="<?= (int) $page ?>">
                        <label class="visually-hidden" for="comment-<?= (int) $image['id'] ?>">Ajouter un commentaire</label>
                        <textarea id="comment-<?= (int) $image['id'] ?>" name="content" rows="2" maxlength="500"
                                  placeholder="Ajouter un commentaire…" required></textarea>
                        <button type="submit" class="btn btn--primary">Envoyer</button>
                    </form>
                <?php else: ?>
                    <p class="comment-login muted"><a href="/login">Connectez-vous</a> pour commenter ou aimer les images.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- data-page/data-total-pages : état lu par gallery.js pour le défilement infini.
         data-full-nav sur Précédent : navigation classique même en mode AJAX. -->
    <nav class="pagination" aria-label="Pagination"
         data-page="<?= (int) $page ?>" data-total-pages="<?= (int) $totalPages ?>">
        <?php if ($page > 1): ?>
            <a class="btn" data-ajax-page="<?= $page - 1 ?>" data-full-nav href="/gallery?page=<?= $page - 1 ?>">← Précédent</a>
        <?php endif; ?>
        <span class="pagination-info">Page <?= (int) $page ?> / <?= (int) $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a class="btn" data-ajax-page="<?= $page + 1 ?>" href="/gallery?page=<?= $page + 1 ?>">Suivant →</a>
        <?php endif; ?>
    </nav>

    <!-- Sentinelle observée par IntersectionObserver pour charger la page suivante. -->
    <div id="gallery-sentinel" aria-hidden="true"></div>
<?php endif; ?>
