<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Core\Database;
use App\Repositories\UserRepository;
use App\Services\NotificationService;
use Tests\FakeMailer;
use Tests\TestCase;

/**
 * Les gardes de NotificationService (auto-commentaire, auteur introuvable,
 * préférence désactivée) doivent être franchies sans effet de bord ni
 * exception. L'envoi effectif (Mailer → MailHog en dev) est vérifié par le
 * parcours HTTP, pas ici : l'environnement CI n'a pas de relais mail.
 */
final class NotificationServiceTest extends TestCase
{
    private UserRepository $users;
    private NotificationService $notifications;
    private FakeMailer $mailer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository(Database::pdo());
        $this->mailer = new FakeMailer();
        $this->notifications = new NotificationService($this->users, $this->mailer);
    }

    public function testNotificationGuardsDoNotCrash(): void
    {
        $alice = $this->users->create('alice', 'alice@example.com', 'hash1');
        $bob = $this->users->create('bob', 'bob@example.com', 'hash1');
        $this->users->updateProfile($alice, 'alice', 'alice@example.com', false); // notifications off

        // Commentaire sur sa propre image : aucune notification.
        $this->notifications->notifyComment($alice, $alice, 'alice', 'Bravo !');

        // Auteur introuvable : ignoré sans erreur.
        $this->notifications->notifyComment(999999, $bob, 'bob', 'Hello');

        // Préférence désactivée : ignoré sans erreur.
        $this->notifications->notifyComment($alice, $bob, 'bob', 'Hello');

        // Préférence activée : l'email est enregistré par le faux mailer.
        $this->users->updateProfile($alice, 'alice', 'alice@example.com', true);
        $this->notifications->notifyComment($alice, $bob, 'bob', 'Hello');

        self::assertCount(1, $this->mailer->sent);
        self::assertSame('alice@example.com', $this->mailer->sent[0]['to']);
    }
}
