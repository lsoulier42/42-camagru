<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Composition d'images côté serveur (GD) : applique un PNG superposable
 * (canal alpha) sur une photo. Le rendu final est toujours fait ici,
 * jamais sur le client.
 */
final class Compositor
{
    /**
     * Superpose l'overlay sur la photo : redimensionné à 80 % de la largeur,
     * centré horizontalement, centre de l'overlay à 42 % de la hauteur.
     */
    public static function applyOverlay(\GdImage $base, string $overlayPath): \GdImage
    {
        return self::applyOverlayFrame($base, $overlayPath, 1.0, 0);
    }

    /**
     * Comme applyOverlay, mais avec une échelle (0.8 = 80 % de la largeur
     * de base) et un décalage vertical en pixels — utilisé pour animer
     * l'overlay frame par frame (export GIF).
     */
    public static function applyOverlayFrame(\GdImage $base, string $overlayPath, float $scale = 1.0, int $offsetY = 0): \GdImage
    {
        $overlay = imagecreatefrompng($overlayPath);
        if ($overlay === false) {
            throw new \RuntimeException('Overlay illisible : ' . basename($overlayPath));
        }

        $bw = imagesx($base);
        $bh = imagesy($base);
        $ow = imagesx($overlay);
        $oh = imagesy($overlay);

        $targetW = (int) round($bw * 0.80 * $scale);
        $targetW = max(1, $targetW);
        $targetH = (int) round($oh * ($targetW / $ow));

        $dstX = (int) round(($bw - $targetW) / 2);
        $dstY = (int) round($bh * 0.42 - $targetH / 2) + $offsetY;

        imagecopyresampled($base, $overlay, $dstX, $dstY, 0, 0, $targetW, $targetH, $ow, $oh);
        imagesavealpha($base, true);
        imagedestroy($overlay);

        return $base;
    }
}
