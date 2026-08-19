<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Mailer;
use App\Repositories\UserRepository;

/**
 * Notifications par email. Aujourd'hui : alerte « nouveau commentaire »
 * à l'auteur d'une image, selon sa préférence.
 */
final class NotificationService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Mailer $mailer,
    ) {
    }

    /** Notifie l'auteur si ce n'est pas lui-même et si sa préférence est activée. */
    public function notifyComment(int $authorId, int $commenterId, string $commenterUsername, string $content): void
    {
        if ($authorId === $commenterId) {
            return; // pas de notification à soi-même
        }

        $author = $this->users->findById($authorId);
        if ($author === null || !$author->notifyComments) {
            return;
        }

        $link = Env::get('APP_URL', Env::DEFAULT_APP_URL) . '/gallery';
        $body = '<p>Bonjour <strong>' . htmlspecialchars($author->username, ENT_QUOTES) . '</strong>,</p>'
            . '<p>' . htmlspecialchars($commenterUsername, ENT_QUOTES)
            . ' a commenté votre image :</p>'
            . '<blockquote style="margin:1rem 0;padding:.75rem 1rem;border-left:3px solid #e1306c;background:#f6f7f9;">'
            . nl2br(htmlspecialchars($content, ENT_QUOTES)) . '</blockquote>'
            . '<p><a href="' . $link . '">Voir la galerie</a></p>';
        $this->mailer->send($author->email, 'Nouveau commentaire sur votre image', $body);
    }
}
