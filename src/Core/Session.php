<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session : démarrage avec des cookies sécurisés (HttpOnly, SameSite=Lax)
 * et messages flash (succès / erreur) consommés à l'affichage.
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('CAMAGRU_SESSION');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false, // HTTP en dev ; à passer à true derrière HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::$started = true;
    }

    /** Définit ou lit un message flash (auto-consumé à la lecture). */
    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }
}
