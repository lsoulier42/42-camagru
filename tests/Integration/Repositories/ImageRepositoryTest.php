<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Core\Database;
use App\Entities\Image;
use App\Repositories\CommentRepository;
use App\Repositories\ImageRepository;
use App\Repositories\LikeRepository;
use App\Repositories\UserRepository;
use Tests\TestCase;

final class ImageRepositoryTest extends TestCase
{
    private UserRepository $users;
    private ImageRepository $images;
    private LikeRepository $likes;
    private CommentRepository $comments;

    protected function setUp(): void
    {
        parent::setUp();
        $pdo = Database::pdo();
        $this->users = new UserRepository($pdo);
        $this->images = new ImageRepository($pdo);
        $this->likes = new LikeRepository($pdo);
        $this->comments = new CommentRepository($pdo);
    }

    private function createUser(string $name): int
    {
        return $this->users->create($name, $name . '@example.com', 'hash1');
    }

    public function testCreateAndFindById(): void
    {
        $authorId = $this->createUser('alice');
        $imageId = $this->images->create($authorId, 'img_abc.png');
        self::assertGreaterThan(0, $imageId);

        $image = $this->images->findById($imageId);
        self::assertNotNull($image);
        self::assertInstanceOf(Image::class, $image);
        self::assertSame('img_abc.png', $image->filename());
        self::assertSame($authorId, $image->userId());
        self::assertNotNull($image->createdAt());
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        self::assertNull($this->images->findById(999999));
    }

    public function testCountAll(): void
    {
        $authorId = $this->createUser('bob');
        self::assertSame(0, $this->images->countAll());

        $this->images->create($authorId, 'a.png');
        $this->images->create($authorId, 'b.png');

        self::assertSame(2, $this->images->countAll());
    }

    public function testFindByUserReturnsOwnImagesNewestFirst(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');

        $first = $this->images->create($alice, 'first.png');
        $second = $this->images->create($alice, 'second.png');
        $this->images->create($bob, 'bob.png'); // ne doit pas apparaître

        // Les deux images d'alice, la plus récente d'abord.
        $images = $this->images->findByUser($alice);
        self::assertCount(2, $images);
        self::assertSame([$second, $first], array_map(static fn (Image $i) => $i->id(), $images));
    }

    public function testFindPageWithCountersAndLikedFlag(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = $this->images->create($alice, 'img.png');
        $imageId2 = $this->images->create($alice, 'img2.png');

        $this->likes->toggle($imageId, $bob);
        $this->comments->create($imageId, $bob, 'Superbe !');

        // Vue en tant que Bob : l'image 1 est likée, 1 like, 1 commentaire.
        $page = $this->images->findPage(1, 6, $bob);
        self::assertCount(2, $page);
        $first = $page[0];
        self::assertSame($imageId2, $first->id()); // la plus récente d'abord
        self::assertSame('alice', $first->author());
        self::assertSame(0, $first->likesCount());
        self::assertFalse($first->liked());

        $second = $page[1];
        self::assertSame($imageId, $second->id());
        self::assertSame(1, $second->likesCount());
        self::assertSame(1, $second->commentsCount());
        self::assertTrue($second->liked());

        // Vue en tant que l'auteur Alice : le drapeau « liké » est à faux.
        $pageAsAuthor = $this->images->findPage(1, 6, $alice);
        self::assertFalse($pageAsAuthor[1]->liked());
    }

    public function testFindPagePagination(): void
    {
        $author = $this->createUser('alice');
        for ($i = 1; $i <= 7; $i++) {
            $this->images->create($author, "img{$i}.png");
        }

        $page1 = $this->images->findPage(1, 6, 0);
        self::assertCount(6, $page1);
        self::assertSame(7, $this->images->countAll());
    }

    public function testFindForDetail(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = $this->images->create($alice, 'img.png');

        $this->likes->toggle($imageId, $bob);
        $this->comments->create($imageId, $bob, 'Bravo !');

        $detail = $this->images->findForDetail($imageId, $bob);
        self::assertNotNull($detail);
        self::assertSame($imageId, $detail->id());
        self::assertSame('alice', $detail->author());
        self::assertSame(1, $detail->likesCount());
        self::assertSame(1, $detail->commentsCount());
        self::assertTrue($detail->liked());

        self::assertNull($this->images->findForDetail(999999, $bob));
    }

    public function testDeleteOwnedOnlyByOwner(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = $this->images->create($alice, 'img.png');

        // Bob ne peut pas supprimer l'image d'Alice.
        self::assertFalse($this->images->deleteOwned($imageId, $bob));
        self::assertNotNull($this->images->findById($imageId));

        // Alice le peut.
        self::assertTrue($this->images->deleteOwned($imageId, $alice));
        self::assertNull($this->images->findById($imageId));
    }
}
