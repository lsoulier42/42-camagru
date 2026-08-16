<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Moteur de vues minimal : rend un template depuis src/Views et expose
 * un helper d'échappement HTML systématique (anti-XSS).
 */
final class View
{
    /** Échappe toute sortie utilisateur avant affichage. */
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require APP_ROOT . '/src/Views/' . $template . '.php';
    }
}
