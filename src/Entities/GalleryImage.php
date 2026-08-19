<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * DTO de lecture (projection) pour les pages galerie et détail : une image
 * enrichie de l'auteur, des compteurs, du drapeau « aimée par le visiteur »
 * et (après withComments) des commentaires. Immuable, tout en propriétés.
 */
final class GalleryImage
{
    /**
     * @param list<Comment> $comments
     */
    public function __construct(
        public readonly int $id,
        public readonly int $authorId,
        public readonly string $filename,
        public readonly DateTimeImmutable $createdAt,
        public readonly string $author,
        public readonly int $likesCount,
        public readonly int $commentsCount,
        public readonly bool $liked,
        public readonly array $comments,
    ) {
    }

    /**
     * @param array<string, mixed> $row Ligne brute des requêtes de lecture
     *                                  (clés : id, filename, created_at,
     *                                  author_id, author, likes_count,
     *                                  comments_count, liked).
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            authorId: (int) $row['author_id'],
            filename: (string) $row['filename'],
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            author: (string) $row['author'],
            likesCount: (int) $row['likes_count'],
            commentsCount: (int) $row['comments_count'],
            liked: (bool) $row['liked'],
            comments: [],
        );
    }

    /**
     * @param list<Comment> $comments
     */
    public function withComments(array $comments): self
    {
        return new self(
            id: $this->id,
            authorId: $this->authorId,
            filename: $this->filename,
            createdAt: $this->createdAt,
            author: $this->author,
            likesCount: $this->likesCount,
            commentsCount: $this->commentsCount,
            liked: $this->liked,
            comments: $comments,
        );
    }
}
