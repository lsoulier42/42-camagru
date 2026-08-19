<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * Entité commentaire — ligne de la table `comments`.
 * `author` (username) est un enrichissement porté par la jointure users :
 * null quand la requête ne la fait pas.
 */
final class Comment
{
    public function __construct(
        public readonly int $id,
        public readonly int $imageId,
        public readonly int $userId,
        public readonly string $content,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?string $author,
    ) {
    }

    /**
     * @param array<string, mixed> $row Ligne brute de la table `comments`
     *                                  (clés : id, image_id, user_id, content,
     *                                  created_at, et éventuellement author).
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            imageId: (int) $row['image_id'],
            userId: (int) $row['user_id'],
            content: (string) $row['content'],
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            author: isset($row['author']) ? (string) $row['author'] : null,
        );
    }
}
