<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Envoi d'emails avec la fonction mail() de la stdlib.
 * En développement, le conteneur relaye tout vers MailHog (port 1025).
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $from = Env::get('MAIL_FROM', 'noreply@camagru.local');

        $headers = [
            'From: Camagru <' . $from . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        // Sujet encodé UTF-8 (base64) pour éviter tout caractère problématique.
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $sent = @mail($to, $encodedSubject, $htmlBody, implode("\r\n", $headers));
        if ($sent === false) {
            error_log('[Camagru] Échec de l\'envoi de l\'email à ' . $to);
        }

        return $sent;
    }
}
