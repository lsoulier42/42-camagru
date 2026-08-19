<?php

use App\Core\Csrf;
use App\Core\View;
use App\Entities\GalleryImage;

/** @var list<GalleryImage> $images Images de la page courante (avec commentaires). */
/** @var int $page Numéro de page courant. */
/** @var int $totalPages Nombre total de pages. */
/** @var int $total Nombre total d'images. */

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);
?>
<section class="page-head">
    <h1>Galerie</h1>
    <meta name="csrf-token" content="<?= Csrf::token() ?>">
</section>

<div id="gallery-content">
    <?php View::render('gallery/_grid', [
        'images' => $images,
        'page' => $page,
        'totalPages' => $totalPages,
        'total' => $total,
    ]); ?>
</div>

<script src="/assets/js/gallery.js" defer></script>
<?php View::render('layout/footer'); ?>
