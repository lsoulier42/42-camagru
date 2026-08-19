<?php

declare(strict_types=1);

namespace Tests\Unit\Entities;

use App\Entities\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testFromRowMapsAllColumns(): void
    {
        $user = User::fromRow([
            'id' => 7,
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password_hash' => 'hash',
            'is_active' => 1,
            'notify_comments' => 0,
            'created_at' => '2026-08-19 10:30:00',
        ]);

        self::assertSame(7, $user->id);
        self::assertSame('alice', $user->username);
        self::assertSame('alice@example.com', $user->email);
        self::assertSame('hash', $user->passwordHash);
        self::assertTrue($user->isActive);
        self::assertFalse($user->notifyComments);
        self::assertSame('2026-08-19 10:30:00', $user->createdAt->format('Y-m-d H:i:s'));
    }

    public function testFromRowMapsInactiveUser(): void
    {
        $user = User::fromRow([
            'id' => 1,
            'username' => 'bob',
            'email' => 'bob@example.com',
            'password_hash' => 'hash',
            'is_active' => 0,
            'notify_comments' => 1,
            'created_at' => '2026-08-19 10:30:00',
        ]);

        self::assertFalse($user->isActive);
        self::assertTrue($user->notifyComments);
    }
}
