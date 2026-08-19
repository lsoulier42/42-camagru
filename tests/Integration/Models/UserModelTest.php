<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\TestCase;

final class UserModelTest extends TestCase
{
    public function testCreateAndFindById(): void
    {
        $id = User::create('alice', 'alice@example.com', 'hash1');
        self::assertGreaterThan(0, $id);

        $user = User::findById($id);
        self::assertNotNull($user);
        self::assertSame('alice', $user['username']);
        self::assertSame('alice@example.com', $user['email']);
        self::assertSame('hash1', $user['password_hash']);
        self::assertSame(0, (int) $user['is_active']);
        self::assertSame(1, (int) $user['notify_comments']); // préférence activée par défaut
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        self::assertNull(User::findById(999999));
    }

    public function testFindByLoginMatchesUsernameOrEmail(): void
    {
        User::create('bob', 'bob@example.com', 'hash1');

        $byUsername = User::findByLogin('bob');
        self::assertNotNull($byUsername);
        self::assertSame('bob@example.com', $byUsername['email']);

        $byEmail = User::findByLogin('bob@example.com');
        self::assertNotNull($byEmail);
        self::assertSame('bob', $byEmail['username']);
    }

    public function testFindByLoginReturnsNullWhenUnknown(): void
    {
        self::assertNull(User::findByLogin('inexistant'));
    }

    public function testFindByEmail(): void
    {
        User::create('carol', 'carol@example.com', 'hash1');
        $user = User::findByEmail('carol@example.com');
        self::assertNotNull($user);
        self::assertSame('carol', $user['username']);

        self::assertNull(User::findByEmail('autre@example.com'));
    }

    public function testEmailExistsWithAndWithoutExceptId(): void
    {
        $id = User::create('dave', 'dave@example.com', 'hash1');

        self::assertTrue(User::emailExists('dave@example.com'));
        self::assertFalse(User::emailExists('autre@example.com'));
        // ExceptId : le compte lui-même n'est pas compté (édition de profil).
        self::assertFalse(User::emailExists('dave@example.com', $id));
    }

    public function testUsernameExistsWithAndWithoutExceptId(): void
    {
        $id = User::create('eve', 'eve@example.com', 'hash1');

        self::assertTrue(User::usernameExists('eve'));
        self::assertFalse(User::usernameExists('eve', $id));
    }

    public function testActivate(): void
    {
        $id = User::create('frank', 'frank@example.com', 'hash1');
        self::assertSame(0, (int) User::findById($id)['is_active']);

        User::activate($id);

        self::assertSame(1, (int) User::findById($id)['is_active']);
    }

    public function testUpdateProfile(): void
    {
        $id = User::create('grace', 'grace@example.com', 'hash1');

        User::updateProfile($id, 'grace2', 'grace2@example.com', false);

        $user = User::findById($id);
        self::assertSame('grace2', $user['username']);
        self::assertSame('grace2@example.com', $user['email']);
        self::assertSame(0, (int) $user['notify_comments']);
    }

    public function testUpdatePassword(): void
    {
        $id = User::create('heidi', 'heidi@example.com', password_hash('Oldpass123', PASSWORD_DEFAULT));

        User::updatePassword($id, password_hash('Newpass456', PASSWORD_DEFAULT));

        $user = User::findById($id);
        self::assertTrue(password_verify('Newpass456', (string) $user['password_hash']));
        self::assertFalse(password_verify('Oldpass123', (string) $user['password_hash']));
    }
}
