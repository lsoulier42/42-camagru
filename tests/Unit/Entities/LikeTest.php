<?php

declare(strict_types=1);

namespace Tests\Unit\Entities;

use App\Entities\Like;
use PHPUnit\Framework\TestCase;

final class LikeTest extends TestCase
{
    public function testFromRowMapsAllColumns(): void
    {
        $like = Like::fromRow([
            'id' => 9,
            'image_id' => 12,
            'user_id' => 7,
            'created_at' => '2026-08-19 11:05:00',
        ]);

        self::assertSame(9, $like->id());
        self::assertSame(12, $like->imageId());
        self::assertSame(7, $like->userId());
        self::assertSame('2026-08-19 11:05:00', $like->createdAt()->format('Y-m-d H:i:s'));
    }
}
