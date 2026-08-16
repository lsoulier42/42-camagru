<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\Comment;
use App\Models\Image;
use App\Models\Like;
use App\Models\User;

final class GalleryController extends BaseController
{
    private const int PER_PAGE = 6; // exigence du sujet : au moins 5 par page

    public function index(): void
    {
        $total = Image::countAll();
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        $page = (int) Request::get('page', '1');
        $page = max(1, min($page, $totalPages));

        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        $images = Image::findPage($page, self::PER_PAGE, $viewerId);

        // Commentaires pré-chargés pour chaque image de la page.
        foreach ($images as &$image) {
            $image['comments'] = Comment::findForImage((int) $image['id']);
        }
        unset($image);

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

    public function like(): void
    {
        $this->verifyCsrf();
        if (!$this->requireLogin('Connectez-vous pour aimer des images.')) {
            return;
        }

        $imageId = (int) Request::post('image_id');
        $page = max(1, (int) Request::post('page', '1'));

        if (Image::findById($imageId) === null) {
            if ($this->isAjax()) {
                $this->jsonResponse(['ok' => false, 'error' => 'Cette image n\'existe plus.'], 404);
            }
            Session::flash('error', 'Cette image n\'existe plus.');
            $this->redirect('/gallery?page=' . $page);
        }

        $liked = Like::toggle($imageId, (int) $_SESSION['user_id']);

        if ($this->isAjax()) {
            $this->jsonResponse([
                'ok' => true,
                'liked' => $liked,
                'count' => Like::countFor($imageId),
            ]);
        }

        $this->redirect('/gallery?page=' . $page . '#image-' . $imageId);
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

        $image = Image::findById($imageId);
        if ($image === null) {
            Session::flash('error', 'Cette image n\'existe plus.');
            $this->redirect('/gallery?page=' . $page);
        }

        if ($content === '' || mb_strlen($content) > 500) {
            if ($this->isAjax()) {
                $this->jsonResponse(['ok' => false, 'error' => 'Le commentaire doit contenir entre 1 et 500 caractères.'], 422);
            }
            Session::flash('error', 'Le commentaire doit contenir entre 1 et 500 caractères.');
            $this->redirect('/gallery?page=' . $page . '#image-' . $imageId);
        }

        $commenterId = (int) $_SESSION['user_id'];
        $commentId = Comment::create($imageId, $commenterId, $content);

        // Notification email à l'auteur (sauf si c'est lui-même ou préférence désactivée).
        $authorId = (int) $image['author_id'];
        if ($authorId !== $commenterId) {
            $author = User::findById($authorId);
            if ($author !== null && (int) $author['notify_comments'] === 1) {
                $link = Env::get('APP_URL', 'http://localhost:8080') . '/gallery';
                $body = '<p>Bonjour <strong>' . htmlspecialchars((string) $author['username'], ENT_QUOTES) . '</strong>,</p>'
                    . '<p>' . htmlspecialchars((string) $_SESSION['username'], ENT_QUOTES)
                    . ' a commenté votre image :</p>'
                    . '<blockquote style="margin:1rem 0;padding:.75rem 1rem;border-left:3px solid #e1306c;background:#f6f7f9;">'
                    . nl2br(htmlspecialchars($content, ENT_QUOTES)) . '</blockquote>'
                    . '<p><a href="' . $link . '">Voir la galerie</a></p>';
                Mailer::send((string) $author['email'], 'Nouveau commentaire sur votre image', $body);
            }
        }

        if ($this->isAjax()) {
            $this->jsonResponse([
                'ok' => true,
                'comment' => [
                    'id' => $commentId,
                    'author' => (string) $_SESSION['username'],
                    'content' => $content,
                    'created_at' => date('d/m/Y H:i'),
                ],
                'commentsCount' => Comment::countFor($imageId),
            ]);
        }

        Session::flash('success', 'Commentaire ajouté.');
        $this->redirect('/gallery?page=' . $page . '#image-' . $imageId);
    }

    /** Rendu du fragment grille + pagination (page complète ou AJAX). */
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
