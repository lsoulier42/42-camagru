<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * Entité like — ligne de la table `likes`. Données pures, sans SQL.
 */
final class Like
{
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

    private function __construct(
        private readonly int $id,
        private readonly int $imageId,
        private readonly int $userId,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function imageId(): int
    {
        return $this->imageId;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
