<?php

declare(strict_types=1);

namespace Tests\Unit\Entities;

use App\Entities\Image;
use PHPUnit\Framework\TestCase;

final class ImageTest extends TestCase
{
    public function testFromRowMapsAllColumns(): void
    {
        $image = Image::fromRow([
            'id' => 12,
            'user_id' => 4,
            'filename' => 'img_abc.png',
            'created_at' => '2026-08-19 10:30:00',
        ]);

        self::assertSame(12, $image->id());
        self::assertSame(4, $image->userId());
        self::assertSame('img_abc.png', $image->filename());
        self::assertSame('2026-08-19 10:30:00', $image->createdAt()->format('Y-m-d H:i:s'));
    }
}
