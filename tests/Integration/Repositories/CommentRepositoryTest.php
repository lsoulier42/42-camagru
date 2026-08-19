<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Core\Database;
use App\Repositories\CommentRepository;
use App\Repositories\ImageRepository;
use App\Repositories\UserRepository;
use Tests\TestCase;

final class CommentRepositoryTest extends TestCase
{
    private UserRepository $users;
    private ImageRepository $images;
    private CommentRepository $comments;

    protected function setUp(): void
    {
        parent::setUp();
        $pdo = Database::pdo();
        $this->users = new UserRepository($pdo);
        $this->images = new ImageRepository($pdo);
        $this->comments = new CommentRepository($pdo);
    }

    private function createUser(string $name): int
    {
        return $this->users->create($name, $name . '@example.com', 'hash1');
    }

    public function testCreateAndFindForImage(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = $this->images->create($alice, 'img.png');

        $firstId = $this->comments->create($imageId, $bob, 'Premier commentaire');
        $secondId = $this->comments->create($imageId, $alice, 'Deuxième commentaire');
        self::assertGreaterThan(0, $firstId);
        self::assertGreaterThan($firstId, $secondId);

        $comments = $this->comments->findForImage($imageId);
        self::assertCount(2, $comments);
        self::assertSame('Premier commentaire', $comments[0]->content());
        self::assertSame('Deuxième commentaire', $comments[1]->content());
        self::assertSame('bob', $comments[0]->author()); // jointure utilisateur
        self::assertSame('alice', $comments[1]->author());
        self::assertSame($imageId, $comments[0]->imageId());
        self::assertSame($bob, $comments[0]->userId());
    }

    public function testFindForImageReturnsEmptyWhenNone(): void
    {
        $alice = $this->createUser('alice');
        $imageId = $this->images->create($alice, 'img.png');

        self::assertSame([], $this->comments->findForImage($imageId));
    }

    public function testCountFor(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = $this->images->create($alice, 'img.png');

        self::assertSame(0, $this->comments->countFor($imageId));

        $this->comments->create($imageId, $bob, 'Un');
        $this->comments->create($imageId, $bob, 'Deux');

        self::assertSame(2, $this->comments->countFor($imageId));
    }
}
