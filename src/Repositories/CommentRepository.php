<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Comment;
use PDO;

/**
 * Accès aux données des commentaires.
 */
final class CommentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $imageId, int $userId, string $content): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (image_id, user_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$imageId, $userId, $content]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Commentaires d'une image, du plus ancien au plus récent (avec auteur).
     *
     * @return list<Comment>
     */
    public function findForImage(int $imageId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.image_id, c.user_id, c.content, c.created_at,
                    u.username AS author
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.image_id = ?
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute([$imageId]);

        return array_map(static fn (array $row) => Comment::fromRow($row), $stmt->fetchAll());
    }

    public function countFor(int $imageId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM comments WHERE image_id = ?'
        );
        $stmt->execute([$imageId]);

        return (int) $stmt->fetchColumn();
    }
}
