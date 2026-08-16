<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Like
{
    /**
     * Bascule un like : supprime s'il existe, sinon insère.
     * Renvoie le nouvel état (true = liké, false = déliké).
     */
    public static function toggle(int $imageId, int $userId): bool
    {
        $delete = Database::pdo()->prepare(
            'DELETE FROM likes WHERE image_id = ? AND user_id = ?'
        );
        $delete->execute([$imageId, $userId]);

        if ($delete->rowCount() > 0) {
            return false;
        }

        $insert = Database::pdo()->prepare(
            'INSERT INTO likes (image_id, user_id) VALUES (?, ?)'
        );
        $insert->execute([$imageId, $userId]);

        return true;
    }

    public static function countFor(int $imageId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM likes WHERE image_id = ?'
        );
        $stmt->execute([$imageId]);

        return (int) $stmt->fetchColumn();
    }
}
