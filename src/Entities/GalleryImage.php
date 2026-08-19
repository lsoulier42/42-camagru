<?php

declare(strict_types=1);

namespace App\Entities;

use DateTimeImmutable;

/**
 * DTO de lecture pour les pages galerie et détail : une `Image` enrichie
 * de l'auteur, des compteurs, du drapeau « aimée par le visiteur » et
 * (après withComments) des commentaires. Immuable.
 */
final class GalleryImage
{
    /**
     * @param array<string, mixed> $row Ligne brute des requêtes de lecture
     *                                  (clés : id, filename, created_at,
     *                                  author_id, author, likes_count,
     *                                  comments_count, liked).
     */
    public static function fromRow(array $row): self
    {
        return new self(
            image: Image::fromRow([
                'id' => $row['id'],
                'user_id' => $row['author_id'],
                'filename' => $row['filename'],
                'created_at' => $row['created_at'],
            ]),
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
            image: $this->image,
            author: $this->author,
            likesCount: $this->likesCount,
            commentsCount: $this->commentsCount,
            liked: $this->liked,
            comments: $comments,
        );
    }

    /**
     * @param list<Comment> $comments
     */
    private function __construct(
        private readonly Image $image,
        private readonly string $author,
        private readonly int $likesCount,
        private readonly int $commentsCount,
        private readonly bool $liked,
        private readonly array $comments,
    ) {
    }

    public function image(): Image
    {
        return $this->image;
    }

    public function id(): int
    {
        return $this->image->id();
    }

    public function filename(): string
    {
        return $this->image->filename();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->image->createdAt();
    }

    public function author(): string
    {
        return $this->author;
    }

    public function likesCount(): int
    {
        return $this->likesCount;
    }

    public function commentsCount(): int
    {
        return $this->commentsCount;
    }

    public function liked(): bool
    {
        return $this->liked;
    }

    /** @return list<Comment> */
    public function comments(): array
    {
        return $this->comments;
    }
}
