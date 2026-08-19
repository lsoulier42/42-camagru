<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Core\Database;
use App\Repositories\ImageRepository;
use App\Repositories\LikeRepository;
use App\Repositories\UserRepository;
use Tests\TestCase;

final class LikeRepositoryTest extends TestCase
{
    private UserRepository $users;
    private ImageRepository $images;
    private LikeRepository $likes;

    protected function setUp(): void
    {
        parent::setUp();
        $pdo = Database::pdo();
        $this->users = new UserRepository($pdo);
        $this->images = new ImageRepository($pdo);
        $this->likes = new LikeRepository($pdo);
    }

    private function createUser(string $name): int
    {
        return $this->users->create($name, $name . '@example.com', 'hash1');
    }

    public function testToggleAddsThenRemovesLike(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = $this->images->create($alice, 'img.png');

        // Premier toggle : like ajouté.
        self::assertTrue($this->likes->toggle($imageId, $bob));
        self::assertSame(1, $this->likes->countFor($imageId));

        // Second toggle : like retiré.
        self::assertFalse($this->likes->toggle($imageId, $bob));
        self::assertSame(0, $this->likes->countFor($imageId));
    }

    public function testCountForAggregatesAllUsers(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $carol = $this->createUser('carol');
        $imageId = $this->images->create($alice, 'img.png');

        $this->likes->toggle($imageId, $bob);
        $this->likes->toggle($imageId, $carol);

        self::assertSame(2, $this->likes->countFor($imageId));

        // Un utilisateur ne peut liker qu'une seule fois.
        $this->likes->toggle($imageId, $bob);
        self::assertSame(1, $this->likes->countFor($imageId));
    }
}
