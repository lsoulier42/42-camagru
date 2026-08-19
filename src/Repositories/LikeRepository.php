<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Accès aux données des likes (contrainte d'unicité gérée par la table).
 */
final class LikeRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Bascule un like : supprime s'il existe, sinon insère.
     * Renvoie le nouvel état (true = liké, false = déliké).
     */
    public function toggle(int $imageId, int $userId): bool
    {
        $delete = $this->pdo->prepare(
            'DELETE FROM likes WHERE image_id = ? AND user_id = ?'
        );
        $delete->execute([$imageId, $userId]);

        if ($delete->rowCount() > 0) {
            return false;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO likes (image_id, user_id) VALUES (?, ?)'
        );
        $insert->execute([$imageId, $userId]);

        return true;
    }

    public function countFor(int $imageId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM likes WHERE image_id = ?'
        );
        $stmt->execute([$imageId]);

        return (int) $stmt->fetchColumn();
    }
}
