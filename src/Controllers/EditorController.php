<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Compositor;
use App\Core\GifEncoder;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\Image;
use GdImage;

final class EditorController extends BaseController
{
    private const int MAX_UPLOAD_BYTES = 8 * 1024 * 1024;
    private const int MAX_DIMENSION = 2048;
    private const int PHOTO_W = 480;
    private const int PHOTO_H = 360;

    public function index(): void
    {
        if (!$this->requireLogin('Connectez-vous pour accéder à l\'éditeur.')) {
            return;
        }

        View::render('editor/index', [
            'pageTitle' => 'Éditeur — Camagru',
            'overlays' => self::listOverlays(),
            'myImages' => Image::findByUser((int) $_SESSION['user_id']),
        ]);
    }

    /** Capture webcam : image base64 envoyée par le client, composée côté serveur. */
    public function capture(): void
    {
        $this->verifyCsrf();
        if (!$this->requireLogin('Connectez-vous pour accéder à l\'éditeur.')) {
            return;
        }

        $overlay = Request::post('overlay');
        if (!$this->validOverlay($overlay)) {
            Session::flash('error', 'Image superposable invalide.');
            $this->redirect('/editor');
        }

        $dataUrl = Request::post('image');
        if (preg_match('#^data:image/(png|jpeg|webp);base64,(.+)$#s', $dataUrl, $m) !== 1) {
            Session::flash('error', 'Capture invalide.');
            $this->redirect('/editor');
        }

        $raw = base64_decode($m[2], true);
        $base = $raw !== false ? @imagecreatefromstring($raw) : false;
        if ($base === false) {
            Session::flash('error', 'Capture invalide.');
            $this->redirect('/editor');
        }
        if (imagesx($base) > self::MAX_DIMENSION || imagesy($base) > self::MAX_DIMENSION) {
            imagedestroy($base);
            Session::flash('error', 'Capture trop grande.');
            $this->redirect('/editor');
        }

        try {
            $base = Compositor::applyOverlay($base, self::overlayPath($overlay));
        } catch (\RuntimeException $e) {
            imagedestroy($base);
            error_log('[Camagru] ' . $e->getMessage());
            Session::flash('error', 'Impossible de composer l\'image.');
            $this->redirect('/editor');
        }

        $this->saveImage($base);
    }

    /**
     * Export GIF animé : la même capture webcam, mais l'overlay est animé
     * (pulsation d'échelle + flottement vertical) par un encodage GIF89a
     * généré côté serveur en PHP pur.
     */
    public function gif(): void
    {
        $this->verifyCsrf();
        if (!$this->requireLogin('Connectez-vous pour accéder à l\'éditeur.')) {
            return;
        }

        $overlay = Request::post('overlay');
        if (!$this->validOverlay($overlay)) {
            Session::flash('error', 'Image superposable invalide.');
            $this->redirect('/editor');
        }

        $dataUrl = Request::post('image');
        if (preg_match('#^data:image/(png|jpeg|webp);base64,(.+)$#s', $dataUrl, $m) !== 1) {
            Session::flash('error', 'Capture invalide.');
            $this->redirect('/editor');
        }

        $raw = base64_decode($m[2], true);
        $base = $raw !== false ? @imagecreatefromstring($raw) : false;
        if ($base === false) {
            Session::flash('error', 'Capture invalide.');
            $this->redirect('/editor');
        }
        if (imagesx($base) > self::MAX_DIMENSION || imagesy($base) > self::MAX_DIMENSION) {
            imagedestroy($base);
            Session::flash('error', 'Capture trop grande.');
            $this->redirect('/editor');
        }

        $base = self::fitToPhoto($base); // 480x360, comme la webcam

        // 10 frames : l'overlay pulse (0.85 → 1.05) et flotte (±6 px).
        $frames = [];
        $frameCount = 10;
        for ($i = 0; $i < $frameCount; $i++) {
            $frame = self::cloneImage($base);
            $scale = 0.85 + 0.20 * sin(M_PI * $i / ($frameCount - 1));
            $offsetY = (int) round(6 * sin(2 * M_PI * $i / $frameCount));

            try {
                Compositor::applyOverlayFrame($frame, self::overlayPath($overlay), $scale, $offsetY);
            } catch (\RuntimeException $e) {
                imagedestroy($frame);
                foreach ($frames as $f) {
                    imagedestroy($f);
                }
                error_log('[Camagru] ' . $e->getMessage());
                Session::flash('error', 'Impossible de composer l\'image.');
                $this->redirect('/editor');
            }
            $frames[] = $frame;
        }
        imagedestroy($base);

        try {
            $gif = GifEncoder::encode($frames, 120);
        } catch (\RuntimeException $e) {
            foreach ($frames as $f) {
                imagedestroy($f);
            }
            error_log('[Camagru] ' . $e->getMessage());
            Session::flash('error', 'Impossible de générer le GIF animé.');
            $this->redirect('/editor');
        }
        foreach ($frames as $f) {
            imagedestroy($f);
        }

        $this->saveGif($gif);
    }

    /** Upload d'image : validation stricte (MIME + extension + contenu) puis ré-encodage. */
    public function upload(): void
    {
        $this->verifyCsrf();
        if (!$this->requireLogin('Connectez-vous pour accéder à l\'éditeur.')) {
            return;
        }

        $overlay = Request::post('overlay');
        if (!$this->validOverlay($overlay)) {
            Session::flash('error', 'Image superposable invalide.');
            $this->redirect('/editor');
        }

        $file = $_FILES['photo'] ?? null;
        if ($file === null || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Aucun fichier reçu ou erreur d\'upload.');
            $this->redirect('/editor');
        }
        if ($file['size'] <= 0 || $file['size'] > self::MAX_UPLOAD_BYTES) {
            Session::flash('error', 'Fichier trop volumineux (8 Mo max).');
            $this->redirect('/editor');
        }

        // 1) Type MIME réel (finfo) — pas la seule extension.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            Session::flash('error', 'Type de fichier refusé (images JPEG, PNG ou GIF uniquement).');
            $this->redirect('/editor');
        }

        // 2) Extension.
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            Session::flash('error', 'Extension de fichier refusée.');
            $this->redirect('/editor');
        }

        // 3) Contenu réellement décodable par GD (faux PNG → refusé).
        $raw = (string) file_get_contents((string) $file['tmp_name']);
        $base = @imagecreatefromstring($raw);
        if ($base === false) {
            Session::flash('error', 'Fichier invalide : ce n\'est pas une image lisible.');
            $this->redirect('/editor');
        }

        // 4) Ré-encodage : taille ramenée à la taille webcam, tout payload supprimé.
        $base = self::fitToPhoto($base);

        try {
            $base = Compositor::applyOverlay($base, self::overlayPath($overlay));
        } catch (\RuntimeException $e) {
            imagedestroy($base);
            error_log('[Camagru] ' . $e->getMessage());
            Session::flash('error', 'Impossible de composer l\'image.');
            $this->redirect('/editor');
        }

        $this->saveImage($base);
    }

    /** Suppression : uniquement ses propres images, vérifié côté serveur. */
    public function delete(): void
    {
        $this->verifyCsrf();
        if (!$this->requireLogin('Connectez-vous pour accéder à l\'éditeur.')) {
            return;
        }

        $imageId = (int) Request::post('image_id');
        $userId = (int) $_SESSION['user_id'];

        $image = Image::findById($imageId);
        if ($image === null) {
            Session::flash('error', 'Cette image n\'existe plus.');
            $this->redirect('/editor');
        }

        if ((int) $image['author_id'] !== $userId) {
            Session::flash('error', 'Vous ne pouvez supprimer que vos propres images.');
            $this->redirect('/editor');
        }

        $file = APP_ROOT . '/public/uploads/' . $image['filename'];
        if (Image::deleteOwned($imageId, $userId) && is_file($file)) {
            @unlink($file);
        }

        Session::flash('success', 'Image supprimée.');
        $this->redirect('/editor');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private static function overlayDir(): string
    {
        return APP_ROOT . '/public/assets/overlays';
    }

    private static function overlayPath(string $name): string
    {
        return self::overlayDir() . '/' . $name;
    }

    /** Liste des PNG superposables du dossier (fichiers sûrs uniquement).
     *
     * @return list<string>
     */
    private static function listOverlays(): array
    {
        $files = glob(self::overlayDir() . '/*.png');
        if ($files === false) {
            return [];
        }

        $names = array_map('basename', $files);
        sort($names);

        return $names;
    }

    /** Anti traversal : nom de fichier simple, existant dans le dossier. */
    private function validOverlay(string $name): bool
    {
        return $name !== ''
            && preg_match('/^[a-z0-9_-]+\.png$/i', $name) === 1
            && is_file(self::overlayPath($name));
    }

    /** Clone une image vraies couleurs (imageclone n'existe plus en PHP 8). */
    private static function cloneImage(GdImage $image): GdImage
    {
        $clone = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagealphablending($clone, false);
        imagesavealpha($clone, true);
        imagecopy($clone, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $clone;
    }

    /** Redimensionne en 480x360 (couverture) en préservant l'alpha. */
    private static function fitToPhoto(GdImage $image): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);

        if ($w <= self::PHOTO_W && $h <= self::PHOTO_H) {
            return $image;
        }

        $ratio = min(self::PHOTO_W / $w, self::PHOTO_H / $h);
        $nw = (int) round($w * $ratio);
        $nh = (int) round($h * $ratio);

        $resized = imagecreatetruecolor($nw, $nh);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($image);

        return $resized;
    }

    /** Enregistre en PNG (nom aléatoire), insère en base, redirige. */
    private function saveImage(GdImage $image): never
    {
        $uploadsDir = APP_ROOT . '/public/uploads';
        $filename = 'img_' . bin2hex(random_bytes(8)) . '.png';

        if (!imagepng($image, $uploadsDir . '/' . $filename)) {
            imagedestroy($image);
            Session::flash('error', 'Impossible d\'enregistrer l\'image.');
            $this->redirect('/editor');
        }
        imagedestroy($image);

        Image::create((int) $_SESSION['user_id'], $filename);

        Session::flash('success', 'Image publiée dans la galerie !');
        $this->redirect('/editor');
    }

    /** Enregistre le GIF animé (nom aléatoire), insère en base, redirige. */
    private function saveGif(string $contents): never
    {
        $uploadsDir = APP_ROOT . '/public/uploads';
        $filename = 'img_' . bin2hex(random_bytes(8)) . '.gif';

        if (file_put_contents($uploadsDir . '/' . $filename, $contents) === false) {
            Session::flash('error', 'Impossible d\'enregistrer le GIF animé.');
            $this->redirect('/editor');
        }

        Image::create((int) $_SESSION['user_id'], $filename);

        Session::flash('success', 'GIF animé publié dans la galerie !');
        $this->redirect('/editor');
    }
}
