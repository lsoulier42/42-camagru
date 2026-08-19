<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * Entité image — ligne de la table `images`. Données pures, sans SQL.
 */
final class Image
{
    /**
     * @param array<string, mixed> $row Ligne brute de la table `images`
     *                                  (clés : id, user_id, filename, created_at).
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            filename: (string) $row['filename'],
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }

    private function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $filename,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
