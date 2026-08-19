<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * Entité utilisateur — données pures, sans SQL.
 * Construite par UserRepository::fromRow() ; immuable.
 */
final class User
{
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

    private function __construct(
        private readonly int $id,
        private readonly string $username,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly bool $isActive,
        private readonly bool $notifyComments,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function notifyComments(): bool
    {
        return $this->notifyComments;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
