<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\ShareData;
use App\Entities\Comment;
use App\Entities\GalleryImage;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ShareDataTest extends TestCase
{
    public function testDefaultQuoteWhenNoComments(): void
    {
        $share = ShareData::forImage($this->image(), 'http://localhost:8080');

        self::assertSame('Découvrez cette image sur Camagru !', $share->quote);
    }

    public function testUsesLastCommentAsQuote(): void
    {
        $image = $this->image([
            $this->comment('Premier message'),
            $this->comment('   Dernier message   '),
        ]);

        $share = ShareData::forImage($image, 'http://localhost:8080');

        self::assertSame('Dernier message', $share->quote);
    }

    public function testLongQuoteIsTruncated(): void
    {
        $long = str_repeat('a', 200);
        $image = $this->image([$this->comment($long)]);

        $share = ShareData::forImage($image, 'http://localhost:8080');

        // 117 caractères + «…» : la troncature garde MAX_QUOTE_LENGTH - 3.
        self::assertSame(ShareData::MAX_QUOTE_LENGTH - 3 + 1, mb_strlen($share->quote));
        self::assertSame('…', mb_substr($share->quote, -1));
    }

    public function testShareUrlsContainEncodedImageUrl(): void
    {
        $share = ShareData::forImage($this->image(), 'https://camagru.example');

        self::assertStringContainsString('https%3A%2F%2Fcamagru.example%2Fimage%2F42', $share->twitterUrl);
        self::assertStringContainsString('https%3A%2F%2Fcamagru.example%2Fimage%2F42', $share->facebookUrl);
    }

    /** @param list<Comment> $comments */
    private function image(array $comments = []): GalleryImage
    {
        return GalleryImage::fromRow([
            'id' => 42,
            'filename' => 'img_42.png',
            'created_at' => '2026-01-01 12:00:00',
            'author_id' => 7,
            'author' => 'alice',
            'likes_count' => 0,
            'comments_count' => count($comments),
            'liked' => false,
        ])->withComments($comments);
    }

    private function comment(string $content): Comment
    {
        return Comment::fromRow([
            'id' => 1,
            'image_id' => 42,
            'user_id' => 7,
            'content' => $content,
            'created_at' => '2026-01-01 12:00:00',
            'author' => 'alice',
        ]);
    }
}
