<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * Entité jeton à usage unique (confirmation de compte, reset de mot de passe).
 * Représente la ligne de la table `tokens` : la valeur brute envoyée au client
 * n'est jamais stockée, seul son hash SHA-256 l'est.
 */
final class Token
{
    /**
     * @param array<string, mixed> $row Ligne brute de la table `tokens`.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            type: (string) $row['type'],
            hash: (string) $row['hash'],
            expiresAt: new DateTimeImmutable((string) $row['expires_at']),
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }

    private function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $type,
        private readonly string $hash,
        private readonly DateTimeImmutable $expiresAt,
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

    public function type(): string
    {
        return $this->type;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
