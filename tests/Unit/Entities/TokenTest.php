<?php

declare(strict_types=1);

namespace Tests\Unit\Entities;

use App\Entities\Token;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    public function testFromRowMapsAllColumns(): void
    {
        $token = Token::fromRow([
            'id' => 5,
            'user_id' => 3,
            'type' => 'confirm',
            'hash' => str_repeat('a', 64),
            'expires_at' => '2026-08-20 12:00:00',
            'created_at' => '2026-08-19 12:00:00',
        ]);

        self::assertSame(5, $token->id);
        self::assertSame(3, $token->userId);
        self::assertSame('confirm', $token->type);
        self::assertSame(str_repeat('a', 64), $token->hash);
        self::assertSame('2026-08-20 12:00:00', $token->expiresAt->format('Y-m-d H:i:s'));
        self::assertSame('2026-08-19 12:00:00', $token->createdAt->format('Y-m-d H:i:s'));
    }
}
