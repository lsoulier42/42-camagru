<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Core\Database;
use App\Repositories\UserRepository;
use Tests\TestCase;

final class UserRepositoryTest extends TestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository(Database::pdo());
    }

    public function testCreateAndFindById(): void
    {
        $id = $this->users->create('alice', 'alice@example.com', 'hash1');
        self::assertGreaterThan(0, $id);

        $user = $this->users->findById($id);
        self::assertNotNull($user);
        self::assertSame('alice', $user->username);
        self::assertSame('alice@example.com', $user->email);
        self::assertSame('hash1', $user->passwordHash);
        self::assertFalse($user->isActive);
        self::assertTrue($user->notifyComments); // préférence activée par défaut
        self::assertNotNull($user->createdAt);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        self::assertNull($this->users->findById(999999));
    }

    public function testFindByLoginMatchesUsernameOrEmail(): void
    {
        $this->users->create('bob', 'bob@example.com', 'hash1');

        $byUsername = $this->users->findByLogin('bob');
        self::assertNotNull($byUsername);
        self::assertSame('bob@example.com', $byUsername->email);

        $byEmail = $this->users->findByLogin('bob@example.com');
        self::assertNotNull($byEmail);
        self::assertSame('bob', $byEmail->username);
    }

    public function testFindByLoginReturnsNullWhenUnknown(): void
    {
        self::assertNull($this->users->findByLogin('inexistant'));
    }

    public function testFindByEmail(): void
    {
        $this->users->create('carol', 'carol@example.com', 'hash1');
        $user = $this->users->findByEmail('carol@example.com');
        self::assertNotNull($user);
        self::assertSame('carol', $user->username);

        self::assertNull($this->users->findByEmail('autre@example.com'));
    }

    public function testEmailExistsWithAndWithoutExceptId(): void
    {
        $id = $this->users->create('dave', 'dave@example.com', 'hash1');

        self::assertTrue($this->users->emailExists('dave@example.com'));
        self::assertFalse($this->users->emailExists('autre@example.com'));
        // ExceptId : le compte lui-même n'est pas compté (édition de profil).
        self::assertFalse($this->users->emailExists('dave@example.com', $id));
    }

    public function testUsernameExistsWithAndWithoutExceptId(): void
    {
        $id = $this->users->create('eve', 'eve@example.com', 'hash1');

        self::assertTrue($this->users->usernameExists('eve'));
        self::assertFalse($this->users->usernameExists('eve', $id));
    }

    public function testActivate(): void
    {
        $id = $this->users->create('frank', 'frank@example.com', 'hash1');
        self::assertFalse($this->users->findById($id)->isActive);

        $this->users->activate($id);

        self::assertTrue($this->users->findById($id)->isActive);
    }

    public function testUpdateProfile(): void
    {
        $id = $this->users->create('grace', 'grace@example.com', 'hash1');

        $this->users->updateProfile($id, 'grace2', 'grace2@example.com', false);

        $user = $this->users->findById($id);
        self::assertSame('grace2', $user->username);
        self::assertSame('grace2@example.com', $user->email);
        self::assertFalse($user->notifyComments);
    }

    public function testUpdatePassword(): void
    {
        $id = $this->users->create('heidi', 'heidi@example.com', password_hash('Oldpass123', PASSWORD_DEFAULT));

        $this->users->updatePassword($id, password_hash('Newpass456', PASSWORD_DEFAULT));

        $user = $this->users->findById($id);
        self::assertTrue(password_verify('Newpass456', $user->passwordHash));
        self::assertFalse(password_verify('Oldpass123', $user->passwordHash));
    }
}
