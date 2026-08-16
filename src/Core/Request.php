<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lecture des entrées HTTP (GET/POST), trimmées, jamais brutes.
 */
final class Request
{
    public static function get(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
    }

    public static function post(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
    }

    public static function hasPost(string $key): bool
    {
        return isset($_POST[$key]);
    }

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
}
