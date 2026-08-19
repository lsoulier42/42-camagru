<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contrat d'envoi d'emails. Implémenté par Mailer (fonction mail() de la
 * stdlib, relayée vers MailHog en dev) — et par un faux dans les tests
 * unitaires, pour vérifier le contenu des emails sans dépendre du réseau.
 */
interface MailerInterface
{
    public function send(string $to, string $subject, string $htmlBody): bool;
}
