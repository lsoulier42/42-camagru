<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\GalleryImage;
use App\Entities\Image;
use PDO;

/**
 * Accès aux données des images et aux read-models de la galerie.
 */
final class ImageRepository
{
    /** Projection commune à la liste et au détail : auteur, compteurs, drapeau « liké ». */
    private const SELECT_PROJECTION = 'SELECT i.id, i.filename, i.created_at,
        u.id AS author_id, u.username AS author,
        (SELECT COUNT(*) FROM likes l WHERE l.image_id = i.id) AS likes_count,
        (SELECT COUNT(*) FROM comments c WHERE c.image_id = i.id) AS comments_count,
        EXISTS(
            SELECT 1 FROM likes l2
            WHERE l2.image_id = i.id AND l2.user_id = :viewer_id
        ) AS liked
        FROM images i
        JOIN users u ON u.id = i.user_id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS total FROM images');

        return (int) $stmt->fetch()['total'];
    }

    /**
     * Page d'images triées par date de création (récentes d'abord).
     *
     * @return list<GalleryImage>
     */
    public function findPage(int $page, int $perPage, int $viewerId): array
    {
        $sql = self::SELECT_PROJECTION . ' ORDER BY i.created_at DESC, i.id DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':viewer_id', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn (array $row) => GalleryImage::fromRow($row), $stmt->fetchAll());
    }

    public function findById(int $id): ?Image
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, filename, created_at FROM images WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : Image::fromRow($row);
    }

    /**
     * Détail d'une image (page /image/{id}) : mêmes compteurs et drapeau
     * « aimée par le visiteur » que la liste.
     */
    public function findForDetail(int $id, int $viewerId): ?GalleryImage
    {
        $sql = self::SELECT_PROJECTION . ' WHERE i.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':viewer_id', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row === false ? null : GalleryImage::fromRow($row);
    }

    public function create(int $userId, string $filename): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO images (user_id, filename) VALUES (?, ?)'
        );
        $stmt->execute([$userId, $filename]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Images d'un utilisateur, récentes d'abord (vignettes de l'éditeur).
     *
     * @return list<Image>
     */
    public function findByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, filename, created_at FROM images
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([$userId]);

        return array_map(static fn (array $row) => Image::fromRow($row), $stmt->fetchAll());
    }

    /** Supprime une image si (et seulement si) elle appartient à l'utilisateur. */
    public function deleteOwned(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM images WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
