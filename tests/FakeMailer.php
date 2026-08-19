<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Mailer;

/**
 * Faux mailer pour les tests : n'envoie rien, enregistre les emails dans
 * une liste consultable par les assertions (contenu vérifiable sans réseau).
 */
final class FakeMailer extends Mailer
{
    /** @var list<array{to: string, subject: string, htmlBody: string}> */
    public array $sent = [];

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $this->sent[] = [
            'to' => $to,
            'subject' => $subject,
            'htmlBody' => $htmlBody,
        ];

        return true;
    }

    public function reset(): void
    {
        $this->sent = [];
    }
}
