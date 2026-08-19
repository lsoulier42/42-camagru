<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Core\Database;
use App\Models\Comment;
use App\Models\Image;
use App\Models\Like;
use App\Repositories\UserRepository;
use Tests\TestCase;

final class ImageModelTest extends TestCase
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

    public function testCreateAndFindById(): void
    {
        $authorId = $this->createUser('alice');
        $imageId = Image::create($authorId, 'img_abc.png');
        self::assertGreaterThan(0, $imageId);

        $image = Image::findById($imageId);
        self::assertNotNull($image);
        self::assertSame('img_abc.png', $image['filename']);
        self::assertSame($authorId, (int) $image['author_id']);
        self::assertSame('alice', $image['author']); // jointure utilisateur
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        self::assertNull(Image::findById(999999));
    }

    public function testCountAll(): void
    {
        $authorId = $this->createUser('bob');
        self::assertSame(0, Image::countAll());

        Image::create($authorId, 'a.png');
        Image::create($authorId, 'b.png');

        self::assertSame(2, Image::countAll());
    }

    public function testFindByUserReturnsOwnImagesNewestFirst(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');

        $first = Image::create($alice, 'first.png');
        $second = Image::create($alice, 'second.png');
        Image::create($bob, 'bob.png'); // ne doit pas apparaître

        // Les deux images d'alice, la plus récente d'abord.
        $images = Image::findByUser($alice);
        self::assertCount(2, $images);
        self::assertSame([$second, $first], array_map(static fn (array $i) => (int) $i['id'], $images));
    }

    public function testFindPageWithCountersAndLikedFlag(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = Image::create($alice, 'img.png');
        $imageId2 = Image::create($alice, 'img2.png');

        Like::toggle($imageId, $bob);
        Comment::create($imageId, $bob, 'Superbe !');

        // Vue en tant que Bob : l'image 1 est likée, 1 like, 1 commentaire.
        $page = Image::findPage(1, 6, $bob);
        self::assertCount(2, $page);
        $first = $page[0];
        self::assertSame($imageId2, (int) $first['id']); // la plus récente d'abord
        self::assertSame('alice', $first['author']);
        self::assertSame(0, (int) $first['likes_count']);
        self::assertSame(false, (bool) $first['liked']);

        $second = $page[1];
        self::assertSame($imageId, (int) $second['id']);
        self::assertSame(1, (int) $second['likes_count']);
        self::assertSame(1, (int) $second['comments_count']);
        self::assertSame(true, (bool) $second['liked']);

        // Vue en tant que l'auteur Alice : le drapeau « liké » est à faux.
        $pageAsAuthor = Image::findPage(1, 6, $alice);
        self::assertSame(false, (bool) $pageAsAuthor[1]['liked']);
    }

    public function testFindPagePagination(): void
    {
        $author = $this->createUser('alice');
        for ($i = 1; $i <= 7; $i++) {
            Image::create($author, "img{$i}.png");
        }

        $page1 = Image::findPage(1, 6, 0);
        self::assertCount(6, $page1);
        self::assertSame(7, Image::countAll());
    }

    public function testFindForDetail(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = Image::create($alice, 'img.png');

        Like::toggle($imageId, $bob);
        Comment::create($imageId, $bob, 'Bravo !');

        $detail = Image::findForDetail($imageId, $bob);
        self::assertNotNull($detail);
        self::assertSame($imageId, (int) $detail['id']);
        self::assertSame('alice', $detail['author']);
        self::assertSame(1, (int) $detail['likes_count']);
        self::assertSame(1, (int) $detail['comments_count']);
        self::assertSame(true, (bool) $detail['liked']);

        self::assertNull(Image::findForDetail(999999, $bob));
    }

    public function testDeleteOwnedOnlyByOwner(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $imageId = Image::create($alice, 'img.png');

        // Bob ne peut pas supprimer l'image d'Alice.
        self::assertFalse(Image::deleteOwned($imageId, $bob));
        self::assertNotNull(Image::findById($imageId));

        // Alice le peut.
        self::assertTrue(Image::deleteOwned($imageId, $alice));
        self::assertNull(Image::findById($imageId));
    }
}
