<?php

/**
 * Génère les images superposables (PNG avec canal alpha) dans
 * public/assets/overlays. À relancer si besoin :
 *   docker compose exec web php scripts/generate_overlays.php
 */

declare(strict_types=1);

$dir = dirname(__DIR__) . '/public/assets/overlays';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

/** Canvas 512x512, fond transparent. */
function new_canvas(): GdImage
{
    $im = imagecreatetruecolor(512, 512);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    imagefilledrectangle($im, 0, 0, 511, 511, imagecolorallocatealpha($im, 0, 0, 0, 127));
    imagealphablending($im, true);
    return $im;
}

function save_png(GdImage $im, string $path): void
{
    imagesavealpha($im, true);
    imagepng($im, $path);
    imagedestroy($im);
    echo "  " . basename($path) . "\n";
}

/** Points d'une étoile à $points branches. */
function star_points(int $cx, int $cy, int $outer, int $inner, int $points): array
{
    $pts = [];
    for ($i = 0; $i < $points * 2; $i++) {
        $r = $i % 2 === 0 ? $outer : $inner;
        $a = M_PI / 2 + $i * M_PI / $points;
        $pts[] = (int) round($cx + $r * cos($a));
        $pts[] = (int) round($cy - $r * sin($a));
    }
    return $pts;
}

echo "Génération des overlays dans $dir\n";

// --- Moustache ---
$im = new_canvas();
$dark = imagecolorallocatealpha($im, 35, 25, 25, 0);
imagefilledellipse($im, 176, 250, 180, 120, $dark);
imagefilledellipse($im, 336, 250, 180, 120, $dark);
imagefilledrectangle($im, 232, 232, 280, 262, $dark);
imagefilledellipse($im, 102, 210, 72, 56, $dark);
imagefilledellipse($im, 410, 210, 72, 56, $dark);
save_png($im, $dir . '/moustache.png');

// --- Lunettes de soleil ---
$im = new_canvas();
$dark = imagecolorallocatealpha($im, 18, 18, 18, 0);
imagefilledrectangle($im, 224, 228, 288, 268, $dark);          // pont
imagefilledrectangle($im, 92, 210, 224, 302, $dark);           // verre gauche
imagefilledrectangle($im, 288, 210, 420, 302, $dark);          // verre droit
imagefilledellipse($im, 92, 256, 56, 120, $dark);              // arrondi gauche
imagefilledellipse($im, 224, 256, 56, 120, $dark);
imagefilledellipse($im, 288, 256, 56, 120, $dark);
imagefilledellipse($im, 420, 256, 56, 120, $dark);             // arrondi droit
imagefilledrectangle($im, 70, 238, 92, 262, $dark);            // branches
imagefilledrectangle($im, 420, 238, 442, 262, $dark);
save_png($im, $dir . '/sunglasses.png');

// --- Couronne ---
$im = new_canvas();
$gold = imagecolorallocatealpha($im, 255, 200, 40, 0);
imagefilledpolygon($im, [
    96, 340, 96, 180, 170, 260, 256, 150, 342, 260, 416, 180, 416, 340,
], $gold);
imagefilledrectangle($im, 96, 300, 416, 340, $gold);
$red = imagecolorallocatealpha($im, 220, 30, 60, 0);
imagefilledellipse($im, 256, 300, 26, 26, $red);
imagefilledellipse($im, 176, 300, 18, 18, $red);
imagefilledellipse($im, 336, 300, 18, 18, $red);
save_png($im, $dir . '/crown.png');

// --- Cœur ---
$im = new_canvas();
$pink = imagecolorallocatealpha($im, 235, 60, 110, 0);
imagefilledellipse($im, 196, 190, 180, 180, $pink);
imagefilledellipse($im, 316, 190, 180, 180, $pink);
imagefilledpolygon($im, [132, 220, 380, 220, 256, 398], $pink);
save_png($im, $dir . '/heart.png');

// --- Étoile ---
$im = new_canvas();
$yellow = imagecolorallocatealpha($im, 255, 210, 40, 0);
imagefilledpolygon($im, star_points(256, 268, 190, 80, 5), $yellow);
save_png($im, $dir . '/star.png');

echo "Terminé.\n";
