<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Comment
{
    public static function create(int $imageId, int $userId, string $content): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO comments (image_id, user_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$imageId, $userId, $content]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array<string, mixed>> Commentaires d'une image, du plus ancien au plus récent. */
    public static function findForImage(int $imageId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id, c.content, c.created_at, u.username AS author
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.image_id = ?
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute([$imageId]);

        return $stmt->fetchAll();
    }

    public static function countFor(int $imageId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM comments WHERE image_id = ?'
        );
        $stmt->execute([$imageId]);

        return (int) $stmt->fetchColumn();
    }
}
