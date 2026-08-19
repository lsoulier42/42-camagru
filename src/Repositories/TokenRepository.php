<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Cycle de vie des jetons à usage unique (confirmation, reset de mot de passe).
 * Le jeton renvoyé au client est aléatoire ; seul son hash SHA-256 est stocké
 * en base — un jeton volé en base ne peut pas être deviné.
 */
final class TokenRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Crée un jeton pour un utilisateur et renvoie la valeur brute (une seule fois). */
    public function create(int $userId, string $type, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        // Un seul jeton actif par (utilisateur, type) : purge des anciens.
        $this->deleteFor($userId, $type);

        $stmt = $this->pdo->prepare(
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
    public function findUser(string $type, string $token): ?int
    {
        $hash = hash('sha256', $token);

        $stmt = $this->pdo->prepare(
            'SELECT user_id FROM tokens WHERE type = ? AND hash = ? AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$type, $hash]);
        $row = $stmt->fetch();

        return $row === false ? null : (int) $row['user_id'];
    }

    public function deleteFor(int $userId, string $type): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tokens WHERE user_id = ? AND type = ?');
        $stmt->execute([$userId, $type]);
    }
}
