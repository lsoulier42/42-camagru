<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * Entité utilisateur — données pures, sans SQL.
 * Immuable : propriétés publiques readonly promues au constructeur.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly bool $isActive,
        public readonly bool $notifyComments,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row Ligne brute de la table `users`.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            username: (string) $row['username'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            isActive: (int) $row['is_active'] === 1,
            notifyComments: (int) $row['notify_comments'] === 1,
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
