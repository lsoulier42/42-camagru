<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\Database;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\NotificationService;
use Tests\FakeMailer;
use Tests\TestCase;

/**
 * Contenu des emails vérifié sans réseau : les services injectent
 * FakeMailer (qui enregistre les envois au lieu de les expédier).
 */
final class MailerContentTest extends TestCase
{
    private UserRepository $users;
    private FakeMailer $mailer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository(Database::pdo());
        $this->mailer = new FakeMailer();
    }

    public function testRegisterSendsConfirmationEmailWithTokenLink(): void
    {
        $auth = new AuthService($this->users, new TokenRepository(Database::pdo()), $this->mailer);

        $auth->register('alice', 'alice@example.com', 'Passw0rd!');

        self::assertCount(1, $this->mailer->sent);
        $email = $this->mailer->sent[0];
        self::assertSame('alice@example.com', $email['to']);
        self::assertSame('Confirmez votre compte Camagru', $email['subject']);

        // Le lien de confirmation (jeton) est présent dans le corps.
        self::assertStringContainsString('/confirm?token=', $email['htmlBody']);
        self::assertStringContainsString('alice', $email['htmlBody']);

        // Un jeton de confirmation a bien été créé en base.
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM tokens t JOIN users u ON u.id = t.user_id
             WHERE u.username = 'alice' AND t.type = 'confirm'"
        );
        $stmt->execute();
        self::assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testNotificationEmailEscapesContentAndGoesToAuthor(): void
    {
        $authorId = $this->users->create('carol', 'carol@example.com', 'hash1');
        $this->users->updateProfile($authorId, 'carol', 'carol@example.com', true);
        $commenterId = $this->users->create('dave', 'dave@example.com', 'hash1');

        $notifications = new NotificationService($this->users, $this->mailer);
        $notifications->notifyComment($authorId, $commenterId, 'dave', 'Superbe <img src=x onerror=alert(1)> !');

        self::assertCount(1, $this->mailer->sent);
        $email = $this->mailer->sent[0];
        self::assertSame('carol@example.com', $email['to']);
        self::assertSame('Nouveau commentaire sur votre image', $email['subject']);

        // Le contenu est échappé (anti-XSS) avant insertion dans le HTML.
        self::assertStringContainsString('&lt;img', $email['htmlBody']);
        self::assertStringNotContainsString('<img src=x', $email['htmlBody']);
    }

    public function testSelfCommentDoesNotSendEmail(): void
    {
        $authorId = $this->users->create('carol', 'carol@example.com', 'hash1');
        $this->users->updateProfile($authorId, 'carol', 'carol@example.com', true);

        $notifications = new NotificationService($this->users, $this->mailer);
        $notifications->notifyComment($authorId, $authorId, 'carol', 'Bravo !');

        self::assertSame([], $this->mailer->sent);
    }
}
