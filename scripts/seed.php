<?php

/**
 * Seed de développement (uniquement) : crée des utilisateurs confirmés,
 * 13 images générées côté serveur (GD), des likes et des commentaires.
 *
 * Usage (dans le conteneur) :
 *   docker compose exec web php scripts/seed.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Models\Comment;
use App\Models\Image;
use App\Models\Like;
use App\Repositories\UserRepository;

$uploadsDir = APP_ROOT . '/public/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0775, true);
}

// --- 3 utilisateurs confirmés (idempotent) ---
$userRepo = new UserRepository(Database::pdo());
$users = [];
foreach ([
    ['alice', 'alice@example.com'],
    ['bob', 'bob@example.com'],
    ['carol', 'carol@example.com'],
] as [$name, $email]) {
    // Comptes éventuellement déjà créés (nom ou email existant).
    $user = $userRepo->findByEmail($email) ?? $userRepo->findByLogin($name);
    if ($user === null) {
        $id = $userRepo->create($name, $email, password_hash('Seedpass123', PASSWORD_DEFAULT));
        $userRepo->activate($id);
        $user = $userRepo->findById($id);
    }
    $users[$name] = $user->id();
}

$authors = array_values($users);
$count = count($authors);

// --- 13 images GD avec dates échelonnées (test du tri) ---
$colors = [
    [225, 48, 108],
    [37, 99, 235],
    [22, 163, 74],
    [217, 119, 6],
    [124, 58, 237],
    [14, 165, 233],
];

$pdo = Database::pdo();
for ($i = 1; $i <= 13; $i++) {
    $author = $authors[$i % $count];

    $im = imagecreatetruecolor(480, 360);
    [$r, $g, $b] = $colors[$i % count($colors)];
    imagefilledrectangle($im, 0, 0, 479, 359, imagecolorallocate($im, $r, $g, $b));
    imagefilledellipse($im, 240, 140, 120, 120, imagecolorallocate($im, 255, 255, 255));
    imagestring($im, 5, 200, 285, 'Photo ' . $i, imagecolorallocate($im, 255, 255, 255));

    $filename = 'seed_' . bin2hex(random_bytes(6)) . '.png';
    imagepng($im, $uploadsDir . '/' . $filename);
    imagedestroy($im);

    $imageId = Image::create($author, $filename);

    // Dates échelonnées : photo i datée de il y a (13 - i) minutes.
    $stmt = $pdo->prepare('UPDATE images SET created_at = NOW() - INTERVAL ? MINUTE WHERE id = ?');
    $stmt->execute([13 - $i, $imageId]);

    // Likes et commentaires croisés sur les images paires.
    if ($i % 2 === 0) {
        foreach ($authors as $liker) {
            if ($liker !== $author) {
                Like::toggle($imageId, $liker);
            }
        }
        Comment::create($imageId, $authors[($i + 1) % $count], 'Superbe création, bravo !');
    }
}

echo "Seed terminé : 13 images, 3 utilisateurs.\n";
