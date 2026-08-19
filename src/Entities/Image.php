<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * Entité image — ligne de la table `images`. Données pures, sans SQL.
 */
final class Image
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $filename,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

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
}
