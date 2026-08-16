<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protection CSRF : un jeton aléatoire par session, injecté dans chaque
 * formulaire POST et vérifié à la soumission (comparaison temps constant).
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    /** Champ caché à placer dans chaque formulaire POST. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    public static function verify(?string $token): bool
    {
        $expected = $_SESSION['_csrf'] ?? null;
        if ($expected === null || $token === null || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }
}
