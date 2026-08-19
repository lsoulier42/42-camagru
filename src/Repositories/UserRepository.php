<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\User;
use PDO;

/**
 * Accès aux données des utilisateurs. Le SQL vit ici, jamais dans les entités.
 */
final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromRow($row);
    }

    /** Recherche par username OU email (page de connexion). */
    public function findByLogin(string $login): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$login, $login]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromRow($row);
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        return $this->fieldExists('email', $email, $exceptId);
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        return $this->fieldExists('username', $username, $exceptId);
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$username, $email, $passwordHash]);

        return (int) $this->pdo->lastInsertId();
    }

    public function activate(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function updateProfile(int $id, string $username, string $email, bool $notifyComments): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET username = ?, email = ?, notify_comments = ? WHERE id = ?'
        );
        $stmt->execute([$username, $email, $notifyComments ? 1 : 0, $id]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $id]);
    }

    private function fieldExists(string $field, string $value, ?int $exceptId): bool
    {
        $sql = "SELECT id FROM users WHERE {$field} = ?";
        $params = [$value];
        if ($exceptId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }
}
