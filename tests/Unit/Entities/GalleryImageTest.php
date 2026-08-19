<?php

declare(strict_types=1);

namespace Tests\Unit\Entities;

use App\Entities\Comment;
use App\Entities\GalleryImage;
use PHPUnit\Framework\TestCase;

final class GalleryImageTest extends TestCase
{
    public function testFromRowMapsReadModel(): void
    {
        $image = GalleryImage::fromRow([
            'id' => 12,
            'filename' => 'img_abc.png',
            'created_at' => '2026-08-19 10:30:00',
            'author_id' => 4,
            'author' => 'alice',
            'likes_count' => 3,
            'comments_count' => 2,
            'liked' => 1,
        ]);

        self::assertSame(12, $image->id);
        self::assertSame(4, $image->authorId);
        self::assertSame('img_abc.png', $image->filename);
        self::assertSame('2026-08-19 10:30:00', $image->createdAt->format('Y-m-d H:i:s'));
        self::assertSame('alice', $image->author);
        self::assertSame(3, $image->likesCount);
        self::assertSame(2, $image->commentsCount);
        self::assertTrue($image->liked);
        self::assertSame([], $image->comments);
    }

    public function testWithCommentsIsImmutable(): void
    {
        $image = GalleryImage::fromRow([
            'id' => 12,
            'filename' => 'img_abc.png',
            'created_at' => '2026-08-19 10:30:00',
            'author_id' => 4,
            'author' => 'alice',
            'likes_count' => 3,
            'comments_count' => 2,
            'liked' => 0,
        ]);

        $comment = Comment::fromRow([
            'id' => 1,
            'image_id' => 12,
            'user_id' => 7,
            'content' => 'Bravo !',
            'created_at' => '2026-08-19 11:00:00',
            'author' => 'bob',
        ]);

        $withComments = $image->withComments([$comment]);

        self::assertSame([$comment], $withComments->comments);
        // L'instance d'origine reste inchangée (immuabilité).
        self::assertSame([], $image->comments);
        self::assertSame(12, $withComments->id);
        self::assertSame('alice', $withComments->author);
    }
}
