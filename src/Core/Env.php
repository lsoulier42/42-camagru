<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Parseur de .env maison (~20 lignes, stdlib uniquement).
 * Accepte des lignes `CLE=valeur`, les commentaires `#` et les valeurs
 * entre guillemets simples ou doubles.
 */
final class Env
{
    /** Valeur par défaut de APP_URL (développement local). */
    public const string DEFAULT_APP_URL = 'http://localhost:8080';

    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if (strlen($value) >= 2
                && (($value[0] === '"' && $value[-1] === '"')
                    || ($value[0] === "'" && $value[-1] === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                self::$values[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$values[$key] ?? $default;
    }

    /** Surcharge une valeur (priorité sur le .env) — utilisé par les tests. */
    public static function set(string $key, string $value): void
    {
        self::$values[$key] = $value;
    }
}
