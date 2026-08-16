<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Image
{
    public static function countAll(): int
    {
        $stmt = Database::pdo()->query('SELECT COUNT(*) AS total FROM images');
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Page d'images triées par date de création (récentes d'abord),
     * avec auteur, compteurs et drapeau « aimée par le visiteur ».
     *
     * @return list<array<string, mixed>>
     */
    public static function findPage(int $page, int $perPage, int $viewerId): array
    {
        $sql = 'SELECT i.id, i.filename, i.created_at,
                       u.id AS author_id, u.username AS author,
                       (SELECT COUNT(*) FROM likes l WHERE l.image_id = i.id) AS likes_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.image_id = i.id) AS comments_count,
                       EXISTS(
                           SELECT 1 FROM likes l2
                           WHERE l2.image_id = i.id AND l2.user_id = :viewer_id
                       ) AS liked
                FROM images i
                JOIN users u ON u.id = i.user_id
                ORDER BY i.created_at DESC, i.id DESC
                LIMIT :limit OFFSET :offset';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->bindValue(':viewer_id', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT i.id, i.filename, i.user_id AS author_id, u.username AS author, i.created_at
             FROM images i
             JOIN users u ON u.id = i.user_id
             WHERE i.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function create(int $userId, string $filename): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO images (user_id, filename) VALUES (?, ?)'
        );
        $stmt->execute([$userId, $filename]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** Images d'un utilisateur, récentes d'abord (vignettes de l'éditeur). */
    public static function findByUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, filename, created_at FROM images
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /** Supprime une image si (et seulement si) elle appartient à l'utilisateur. */
    public static function deleteOwned(int $id, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM images WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
