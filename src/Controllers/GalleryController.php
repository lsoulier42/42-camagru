<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Entities\GalleryImage;
use App\Repositories\CommentRepository;
use App\Repositories\ImageRepository;
use App\Repositories\LikeRepository;
use App\Services\NotificationService;

final class GalleryController extends BaseController
{
    private const int PER_PAGE = 6; // exigence du sujet : au moins 5 par page

    public function __construct(
        private readonly ImageRepository $images,
        private readonly CommentRepository $comments,
        private readonly LikeRepository $likes,
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(): void
    {
        $total = $this->images->countAll();
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        $page = (int) Request::get('page', '1');
        $page = max(1, min($page, $totalPages));

        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        $images = $this->images->findPage($page, self::PER_PAGE, $viewerId);

        // Commentaires pré-chargés pour chaque image de la page (DTO immuable).
        $images = array_map(
            fn (GalleryImage $image) => $image->withComments($this->comments->findForImage($image->id)),
            $images
        );

        // Pagination AJAX : on renvoie le fragment HTML à insérer.
        if ($this->isAjax()) {
            $this->jsonResponse([
                'ok' => true,
                'page' => $page,
                'totalPages' => $totalPages,
                'total' => $total,
                'html' => $this->renderGrid($images, $page, $totalPages, $total),
            ]);
        }

        View::render('gallery/index', [
            'pageTitle' => 'Galerie — Camagru',
            'images' => $images,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    /** Page de détail d'une image (partage social, focus sur les commentaires). */
    public function show(string $id): void
    {
        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        $image = $this->images->findForDetail((int) $id, $viewerId);

        if ($image === null) {
            http_response_code(404);
            View::render('error/404', ['pageTitle' => 'Introuvable — Camagru']);
            return;
        }

        $image = $image->withComments($this->comments->findForImage($image->id));

        View::render('gallery/show', [
            'pageTitle' => 'Image de ' . $image->author . ' — Camagru',
            'image' => $image,
        ]);
    }

    public function like(): void
    {
        $this->verifyCsrf();
        if (!$this->requireLogin('Connectez-vous pour aimer des images.')) {
            return;
        }

        $imageId = (int) Request::post('image_id');
        $page = max(1, (int) Request::post('page', '1'));

        if ($this->images->findById($imageId) === null) {
            if ($this->isAjax()) {
                $this->jsonResponse(['ok' => false, 'error' => 'Cette image n\'existe plus.'], 404);
            }
            Session::flash('error', 'Cette image n\'existe plus.');
            $this->redirect('/gallery?page=' . $page);
        }

        $liked = $this->likes->toggle($imageId, (int) $_SESSION['user_id']);

        if ($this->isAjax()) {
            $this->jsonResponse([
                'ok' => true,
                'liked' => $liked,
                'count' => $this->likes->countFor($imageId),
            ]);
        }

        $this->redirect($this->returnPath($imageId, $page));
    }

    public function comment(): void
    {
        $this->verifyCsrf();
        if (!$this->requireLogin('Connectez-vous pour commenter les images.')) {
            return;
        }

        $imageId = (int) Request::post('image_id');
        $content = trim(Request::post('content'));
        $page = max(1, (int) Request::post('page', '1'));

        $image = $this->images->findById($imageId);
        if ($image === null) {
            Session::flash('error', 'Cette image n\'existe plus.');
            $this->redirect('/gallery?page=' . $page);
        }

        if ($content === '' || mb_strlen($content) > 500) {
            if ($this->isAjax()) {
                $this->jsonResponse(['ok' => false, 'error' => 'Le commentaire doit contenir entre 1 et 500 caractères.'], 422);
            }
            Session::flash('error', 'Le commentaire doit contenir entre 1 et 500 caractères.');
            $this->redirect($this->returnPath($imageId, $page));
        }

        $commenterId = (int) $_SESSION['user_id'];
        $commentId = $this->comments->create($imageId, $commenterId, $content);

        // Notification email à l'auteur (sauf si c'est lui-même ou préférence désactivée).
        $this->notifications->notifyComment(
            $image->userId,
            $commenterId,
            (string) $_SESSION['username'],
            $content
        );

        if ($this->isAjax()) {
            $this->jsonResponse([
                'ok' => true,
                'comment' => [
                    'id' => $commentId,
                    'author' => (string) $_SESSION['username'],
                    'content' => $content,
                    'created_at' => date('d/m/Y H:i'),
                ],
                'commentsCount' => $this->comments->countFor($imageId),
            ]);
        }

        Session::flash('success', 'Commentaire ajouté.');
        $this->redirect($this->returnPath($imageId, $page));
    }

    /**
     * Chemin de retour après like/commentaire : le formulaire peut fournir
     * return_path (page de détail), sinon la galerie ancrée. Validation
     * stricte anti open-redirect : chemin relatif, jamais de schéma/protocole.
     */
    private function returnPath(int $imageId, int $page): string
    {
        $default = '/gallery?page=' . $page . '#image-' . $imageId;
        $return = (string) Request::post('return_path', '');

        if ($return !== '' && str_starts_with($return, '/') && !str_starts_with($return, '//') && !str_contains($return, '\\')) {
            return $return;
        }

        return $default;
    }

    /**
     * Rendu du fragment grille + pagination (page complète ou AJAX).
     *
     * @param list<GalleryImage> $images
     */
    private function renderGrid(array $images, int $page, int $totalPages, int $total): string
    {
        ob_start();
        View::render('gallery/_grid', [
            'images' => $images,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);

        return (string) ob_get_clean();
    }
}
