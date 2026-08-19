<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Core\Database;
use App\Models\Image;
use App\Models\Like;
use App\Repositories\UserRepository;
use Tests\TestCase;

final class LikeModelTest extends TestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository(Database::pdo());
    }

    private function createUser(string $name): int
    {
        return $this->users->create($name, $name . '@example.com', 'hash1');
    }

    public function testToggleAddsThenRemovesLike(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
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
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $carol = $this->createUser('carol');
        $imageId = Image::create($alice, 'img.png');

        Like::toggle($imageId, $bob);
        Like::toggle($imageId, $carol);

        self::assertSame(2, Like::countFor($imageId));

        // Un utilisateur ne peut liker qu'une seule fois.
        Like::toggle($imageId, $bob);
        self::assertSame(1, Like::countFor($imageId));
    }
}
