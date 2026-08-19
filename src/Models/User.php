<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /** @return array<string, mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Recherche par username OU email (page de connexion).
     *
     * @return array<string, mixed>|null
     */
    public static function findByLogin(string $login): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$login, $login]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        return self::fieldExists('email', $email, $exceptId);
    }

    public static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        return self::fieldExists('username', $username, $exceptId);
    }

    public static function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$username, $email, $passwordHash]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function activate(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET is_active = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function updateProfile(int $id, string $username, string $email, bool $notifyComments): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users SET username = ?, email = ?, notify_comments = ? WHERE id = ?'
        );
        $stmt->execute([$username, $email, $notifyComments ? 1 : 0, $id]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $id]);
    }

    private static function fieldExists(string $field, string $value, ?int $exceptId): bool
    {
        $sql = "SELECT id FROM users WHERE {$field} = ?";
        $params = [$value];
        if ($exceptId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }
}
