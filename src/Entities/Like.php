<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * Entité like — ligne de la table `likes`. Données pures, sans SQL.
 */
final class Like
{
    public function __construct(
        public readonly int $id,
        public readonly int $imageId,
        public readonly int $userId,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row Ligne brute de la table `likes`
     *                                  (clés : id, image_id, user_id, created_at).
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            imageId: (int) $row['image_id'],
            userId: (int) $row['user_id'],
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
