<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Jetons à usage unique (confirmation de compte, reset de mot de passe).
 * Le jeton renvoyé au client est aléatoire ; seul son hash SHA-256 est
 * stocké en base — un jeton volé en base ne peut pas être deviné.
 */
final class Token
{
    /** Crée un jeton pour un utilisateur et renvoie la valeur brute (une seule fois). */
    public static function create(int $userId, string $type, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        // Un seul jeton actif par (utilisateur, type) : purge des anciens.
        self::deleteFor($userId, $type);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO tokens (user_id, type, hash, expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $type,
            $hash,
            date('Y-m-d H:i:s', time() + $ttlSeconds),
        ]);

        return $token;
    }

    /** Renvoie l'id utilisateur si le jeton est valide (type + non expiré), sinon null. */
    public static function findUser(string $type, string $token): ?int
    {
        $hash = hash('sha256', $token);

        $stmt = Database::pdo()->prepare(
            'SELECT user_id FROM tokens WHERE type = ? AND hash = ? AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$type, $hash]);
        $row = $stmt->fetch();

        return $row === false ? null : (int) $row['user_id'];
    }

    public static function deleteFor(int $userId, string $type): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM tokens WHERE user_id = ? AND type = ?');
        $stmt->execute([$userId, $type]);
    }
}
