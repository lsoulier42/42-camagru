<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Comment;
use App\Models\Image;
use App\Models\User;
use Tests\TestCase;

final class CommentModelTest extends TestCase
{
    public function testCreateAndFindForImage(): void
    {
        $alice = User::create('alice', 'alice@example.com', 'hash1');
        $bob = User::create('bob', 'bob@example.com', 'hash1');
        $imageId = Image::create($alice, 'img.png');

        $firstId = Comment::create($imageId, $bob, 'Premier commentaire');
        $secondId = Comment::create($imageId, $alice, 'Deuxième commentaire');
        self::assertGreaterThan(0, $firstId);
        self::assertGreaterThan($firstId, $secondId);

        $comments = Comment::findForImage($imageId);
        self::assertCount(2, $comments);
        self::assertSame('Premier commentaire', $comments[0]['content']);
        self::assertSame('Deuxième commentaire', $comments[1]['content']);
        self::assertSame('bob', $comments[0]['author']); // jointure utilisateur
        self::assertSame('alice', $comments[1]['author']);
    }

    public function testFindForImageReturnsEmptyWhenNone(): void
    {
        $alice = User::create('alice', 'alice@example.com', 'hash1');
        $imageId = Image::create($alice, 'img.png');

        self::assertSame([], Comment::findForImage($imageId));
    }

    public function testCountFor(): void
    {
        $alice = User::create('alice', 'alice@example.com', 'hash1');
        $bob = User::create('bob', 'bob@example.com', 'hash1');
        $imageId = Image::create($alice, 'img.png');

        self::assertSame(0, Comment::countFor($imageId));

        Comment::create($imageId, $bob, 'Un');
        Comment::create($imageId, $bob, 'Deux');

        self::assertSame(2, Comment::countFor($imageId));
    }
}
