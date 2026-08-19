<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Token;
use App\Models\User;
use Tests\TestCase;

final class TokenModelTest extends TestCase
{
    private function createUser(): int
    {
        return User::create('token_user', 'token@example.com', 'hash1');
    }

    public function testCreateReturnsRawTokenAndFindUserResolvesIt(): void
    {
        $userId = $this->createUser();
        $token = Token::create($userId, 'confirm', 3600);

        self::assertSame(64, strlen($token)); // bin2hex(random_bytes(32))
        self::assertSame($userId, Token::findUser('confirm', $token));
    }

    public function testTokenIsRejectedForWrongType(): void
    {
        $userId = $this->createUser();
        $token = Token::create($userId, 'confirm', 3600);

        self::assertNull(Token::findUser('reset', $token));
    }

    public function testUnknownTokenIsRejected(): void
    {
        $userId = $this->createUser();
        Token::create($userId, 'confirm', 3600);

        self::assertNull(Token::findUser('confirm', 'token-inconnu'));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $userId = $this->createUser();
        $token = Token::create($userId, 'confirm', -10); // déjà expiré

        self::assertNull(Token::findUser('confirm', $token));
    }

    public function testDeleteForInvalidatesToken(): void
    {
        $userId = $this->createUser();
        $token = Token::create($userId, 'confirm', 3600);

        Token::deleteFor($userId, 'confirm');

        self::assertNull(Token::findUser('confirm', $token));
    }

    public function testOnlyOneActiveTokenPerUserAndType(): void
    {
        $userId = $this->createUser();
        $first = Token::create($userId, 'confirm', 3600);
        $second = Token::create($userId, 'confirm', 3600);

        // Le premier est purgé (usage unique), seul le dernier reste valide.
        self::assertNull(Token::findUser('confirm', $first));
        self::assertSame($userId, Token::findUser('confirm', $second));
    }
}
