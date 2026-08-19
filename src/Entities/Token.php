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
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $type,
        public readonly string $hash,
        public readonly DateTimeImmutable $expiresAt,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

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
}
