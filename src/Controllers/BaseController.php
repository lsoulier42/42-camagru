<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;

abstract class BaseController
{
    protected function loggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /** Requête AJAX (fetch natif avec X-Requested-With). */
    protected function isAjax(): bool
    {
        return strtoupper($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHTTPREQUEST';
    }

    /** Redirige vers /login avec un message si non connecté (JSON 401 en AJAX). */
    protected function requireLogin(string $message = 'Vous devez être connecté pour effectuer cette action.'): bool
    {
        if (!$this->loggedIn()) {
            if ($this->isAjax()) {
                $this->jsonResponse(['ok' => false, 'error' => $message], 401);
            }
            Session::flash('error', $message);
            $this->redirect('/login');
        }

        return true;
    }

    /** Vérifie le jeton CSRF de la requête POST ; rejette sinon (JSON 403 en AJAX). */
    protected function verifyCsrf(): void
    {
        if (!Csrf::verify(Request::post('csrf_token'))) {
            if ($this->isAjax()) {
                $this->jsonResponse(['ok' => false, 'error' => 'Jeton de sécurité invalide ou expiré.'], 403);
            }
            http_response_code(403);
            Session::flash('error', 'Jeton de sécurité invalide ou expiré. Veuillez réessayer.');
            $this->redirect('/');
        }
    }

    /** @param array<string, mixed> $data */
    protected function jsonResponse(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}
