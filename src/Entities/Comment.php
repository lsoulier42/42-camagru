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

    private function __construct(
        private readonly int $id,
        private readonly int $imageId,
        private readonly int $userId,
        private readonly string $content,
        private readonly DateTimeImmutable $createdAt,
        private readonly ?string $author,
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

    public function content(): string
    {
        return $this->content;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function author(): ?string
    {
        return $this->author;
    }
}
