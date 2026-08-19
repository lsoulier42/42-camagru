<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Core\Database;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use Tests\TestCase;

final class TokenRepositoryTest extends TestCase
{
    private UserRepository $users;
    private TokenRepository $tokens;

    protected function setUp(): void
    {
        parent::setUp();
        $pdo = Database::pdo();
        $this->users = new UserRepository($pdo);
        $this->tokens = new TokenRepository($pdo);
    }

    private function createUser(): int
    {
        return $this->users->create('token_user', 'token@example.com', 'hash1');
    }

    public function testCreateReturnsRawTokenAndFindUserResolvesIt(): void
    {
        $userId = $this->createUser();
        $token = $this->tokens->create($userId, 'confirm', 3600);

        self::assertSame(64, strlen($token)); // bin2hex(random_bytes(32))
        self::assertSame($userId, $this->tokens->findUser('confirm', $token));
    }

    public function testTokenIsRejectedForWrongType(): void
    {
        $userId = $this->createUser();
        $token = $this->tokens->create($userId, 'confirm', 3600);

        self::assertNull($this->tokens->findUser('reset', $token));
    }

    public function testUnknownTokenIsRejected(): void
    {
        $userId = $this->createUser();
        $this->tokens->create($userId, 'confirm', 3600);

        self::assertNull($this->tokens->findUser('confirm', 'token-inconnu'));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $userId = $this->createUser();
        $token = $this->tokens->create($userId, 'confirm', -10); // déjà expiré

        self::assertNull($this->tokens->findUser('confirm', $token));
    }

    public function testDeleteForInvalidatesToken(): void
    {
        $userId = $this->createUser();
        $token = $this->tokens->create($userId, 'confirm', 3600);

        $this->tokens->deleteFor($userId, 'confirm');

        self::assertNull($this->tokens->findUser('confirm', $token));
    }

    public function testOnlyOneActiveTokenPerUserAndType(): void
    {
        $userId = $this->createUser();
        $first = $this->tokens->create($userId, 'confirm', 3600);
        $second = $this->tokens->create($userId, 'confirm', 3600);

        // Le premier est purgé (usage unique), seul le dernier reste valide.
        self::assertNull($this->tokens->findUser('confirm', $first));
        self::assertSame($userId, $this->tokens->findUser('confirm', $second));
    }
}
