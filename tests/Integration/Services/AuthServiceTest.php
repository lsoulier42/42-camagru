<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Core\Database;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Tests\TestCase;

final class AuthServiceTest extends TestCase
{
    private UserRepository $users;
    private TokenRepository $tokens;
    private AuthService $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $pdo = Database::pdo();
        $this->users = new UserRepository($pdo);
        $this->tokens = new TokenRepository($pdo);
        $this->auth = new AuthService($this->users, $this->tokens);
    }

    private function countTokens(string $type, ?int $userId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM tokens WHERE type = ?';
        $params = [$type];
        if ($userId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function testRegisterCreatesInactiveAccountAndConfirmationToken(): void
    {
        $this->auth->register('alice', 'alice@example.com', 'Passw0rd!');

        $user = $this->users->findByEmail('alice@example.com');
        self::assertNotNull($user);
        self::assertFalse($user->isActive());
        self::assertTrue(password_verify('Passw0rd!', $user->passwordHash()));
        self::assertSame(1, $this->countTokens('confirm', $user->id()));
    }

    public function testConfirmActivatesAccountAndConsumesToken(): void
    {
        $id = $this->users->create('alice', 'alice@example.com', 'hash1');
        $token = $this->tokens->create($id, 'confirm', 3600);

        self::assertTrue($this->auth->confirm($token));
        self::assertTrue($this->users->findById($id)->isActive());

        // Usage unique : le jeton a été supprimé, un second appel échoue.
        self::assertFalse($this->auth->confirm($token));
    }

    public function testConfirmWithUnknownTokenReturnsFalse(): void
    {
        $this->users->create('alice', 'alice@example.com', 'hash1');

        self::assertFalse($this->auth->confirm('token-inconnu'));
    }

    public function testSendResetLinkCreatesTokenOnlyForActiveAccount(): void
    {
        $inactive = $this->users->create('alice', 'alice@example.com', 'hash1');
        $active = $this->users->create('bob', 'bob@example.com', 'hash1');
        $this->users->activate($active);

        $this->auth->sendResetLink('alice@example.com'); // inactif : ignoré
        $this->auth->sendResetLink('bob@example.com');   // actif : jeton créé
        $this->auth->sendResetLink('inconnu@example.com'); // inconnu : ignoré

        self::assertSame(0, $this->countTokens('reset', $inactive));
        self::assertSame(1, $this->countTokens('reset', $active));
    }

    public function testIsResetTokenValid(): void
    {
        $id = $this->users->create('alice', 'alice@example.com', 'hash1');
        $token = $this->tokens->create($id, 'reset', 3600);

        self::assertTrue($this->auth->isResetTokenValid($token));
        self::assertFalse($this->auth->isResetTokenValid('inconnu'));
    }

    public function testResetUpdatesPasswordAndConsumesToken(): void
    {
        $id = $this->users->create('alice', 'alice@example.com', password_hash('Oldpass123', PASSWORD_DEFAULT));
        $token = $this->tokens->create($id, 'reset', 3600);

        self::assertTrue($this->auth->reset($token, 'Newpass456'));

        $user = $this->users->findById($id);
        self::assertTrue(password_verify('Newpass456', $user->passwordHash()));
        self::assertFalse(password_verify('Oldpass123', $user->passwordHash()));

        // Jeton consommé : un second reset échoue.
        self::assertFalse($this->auth->reset($token, 'Another789'));
    }

    public function testResetWithInvalidTokenReturnsFalse(): void
    {
        $this->users->create('alice', 'alice@example.com', 'hash1');

        self::assertFalse($this->auth->reset('inconnu', 'Newpass456'));
    }

    public function testValidateRegistrationRejectsExistingUsernameAndEmail(): void
    {
        $this->users->create('alice', 'alice@example.com', 'hash1');

        $errors = $this->auth->validateRegistration('alice', 'bob@example.com', 'Passw0rd!', 'Passw0rd!');
        self::assertContains('Ce nom d\'utilisateur est déjà pris.', $errors);

        $errors = $this->auth->validateRegistration('bob', 'alice@example.com', 'Passw0rd!', 'Passw0rd!');
        self::assertContains('Cette adresse email est déjà utilisée.', $errors);
    }

    public function testValidateRegistrationAcceptsValidInput(): void
    {
        self::assertSame([], $this->auth->validateRegistration('newuser', 'new@example.com', 'Passw0rd!', 'Passw0rd!'));
    }

    public function testValidateProfileUpdateRejectsTakenUsernameButKeepsOwn(): void
    {
        $aliceId = $this->users->create('alice', 'alice@example.com', 'hash1');
        $this->users->create('bob', 'bob@example.com', 'hash1');
        $alice = $this->users->findById($aliceId);

        // "bob" est pris par un autre compte.
        $errors = $this->auth->validateProfileUpdate($alice, 'bob', 'alice@example.com', '', '', '');
        self::assertContains('Ce nom d\'utilisateur est déjà pris.', $errors);

        // Garder son propre username et email ne déclenche aucune erreur.
        self::assertSame([], $this->auth->validateProfileUpdate($alice, 'alice', 'alice@example.com', '', '', ''));
    }

    public function testValidateProfileUpdateRequiresCurrentPassword(): void
    {
        $id = $this->users->create('alice', 'alice@example.com', password_hash('Current123', PASSWORD_DEFAULT));
        $user = $this->users->findById($id);

        $errors = $this->auth->validateProfileUpdate($user, 'alice', 'alice@example.com', 'mauvais', 'Newpass456', 'Newpass456');
        self::assertContains('Le mot de passe actuel est incorrect.', $errors);

        self::assertSame([], $this->auth->validateProfileUpdate($user, 'alice', 'alice@example.com', 'Current123', 'Newpass456', 'Newpass456'));
    }

    public function testUpdateProfileChangesFieldsAndOptionalPassword(): void
    {
        $id = $this->users->create('alice', 'alice@example.com', password_hash('Oldpass123', PASSWORD_DEFAULT));
        $user = $this->users->findById($id);

        $this->auth->updateProfile($user, 'alice2', 'alice2@example.com', false, 'Newpass456');

        $updated = $this->users->findById($id);
        self::assertSame('alice2', $updated->username());
        self::assertSame('alice2@example.com', $updated->email());
        self::assertFalse($updated->notifyComments());
        self::assertTrue(password_verify('Newpass456', $updated->passwordHash()));

        // Sans nouveau mot de passe : le hash est conservé.
        $this->auth->updateProfile($updated, 'alice2', 'alice2@example.com', false, null);
        self::assertTrue(password_verify('Newpass456', $this->users->findById($id)->passwordHash()));
    }
}
