<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Image;
use App\Models\Like;
use App\Models\User;
use Tests\TestCase;

final class LikeModelTest extends TestCase
{
    public function testToggleAddsThenRemovesLike(): void
    {
        $alice = User::create('alice', 'alice@example.com', 'hash1');
        $bob = User::create('bob', 'bob@example.com', 'hash1');
        $imageId = Image::create($alice, 'img.png');

        // Premier toggle : like ajouté.
        self::assertTrue(Like::toggle($imageId, $bob));
        self::assertSame(1, Like::countFor($imageId));

        // Second toggle : like retiré.
        self::assertFalse(Like::toggle($imageId, $bob));
        self::assertSame(0, Like::countFor($imageId));
    }

    public function testCountForAggregatesAllUsers(): void
    {
        $alice = User::create('alice', 'alice@example.com', 'hash1');
        $bob = User::create('bob', 'bob@example.com', 'hash1');
        $carol = User::create('carol', 'carol@example.com', 'hash1');
        $imageId = Image::create($alice, 'img.png');

        Like::toggle($imageId, $bob);
        Like::toggle($imageId, $carol);

        self::assertSame(2, Like::countFor($imageId));

        // Un utilisateur ne peut liker qu'une seule fois.
        Like::toggle($imageId, $bob);
        self::assertSame(1, Like::countFor($imageId));
    }
}
