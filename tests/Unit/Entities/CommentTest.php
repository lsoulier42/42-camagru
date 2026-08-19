<?php

declare(strict_types=1);

namespace Tests\Unit\Entities;

use App\Entities\Comment;
use PHPUnit\Framework\TestCase;

final class CommentTest extends TestCase
{
    public function testFromRowMapsAllColumnsWithAuthor(): void
    {
        $comment = Comment::fromRow([
            'id' => 3,
            'image_id' => 12,
            'user_id' => 7,
            'content' => 'Superbe !',
            'created_at' => '2026-08-19 11:00:00',
            'author' => 'alice',
        ]);

        self::assertSame(3, $comment->id);
        self::assertSame(12, $comment->imageId);
        self::assertSame(7, $comment->userId);
        self::assertSame('Superbe !', $comment->content);
        self::assertSame('2026-08-19 11:00:00', $comment->createdAt->format('Y-m-d H:i:s'));
        self::assertSame('alice', $comment->author);
    }

    public function testFromRowWithoutAuthorJoin(): void
    {
        $comment = Comment::fromRow([
            'id' => 1,
            'image_id' => 1,
            'user_id' => 1,
            'content' => 'Sans jointure',
            'created_at' => '2026-08-19 11:00:00',
        ]);

        self::assertNull($comment->author);
    }
}
