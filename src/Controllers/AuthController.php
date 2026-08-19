<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\AuthService;

final class AuthController extends BaseController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthService $auth,
    ) {
    }

    // ---------------------------------------------------------------
    // Inscription
    // ---------------------------------------------------------------

    public function showRegister(): void
    {
        if ($this->loggedIn()) {
            $this->redirect('/');
        }

        View::render('auth/register', [
            'pageTitle' => 'Inscription — Camagru',
            'old' => [],
            'errors' => [],
        ]);
    }

    public function register(): void
    {
        $this->verifyCsrf();

        $username = Request::post('username');
        $email = Request::post('email');
        $password = Request::post('password');
        $passwordConfirm = Request::post('password_confirm');

        $errors = $this->auth->validateRegistration($username, $email, $password, $passwordConfirm);
        if ($errors !== []) {
            View::render('auth/register', [
                'pageTitle' => 'Inscription — Camagru',
                'old' => ['username' => $username, 'email' => $email],
                'errors' => $errors,
            ]);
            return;
        }

        $this->auth->register($username, $email, $password);

        Session::flash('success', 'Votre compte a été créé. Un email de confirmation vient de vous être envoyé.');
        $this->redirect('/login');
    }

    // ---------------------------------------------------------------
    // Confirmation de compte (lien unique à usage unique)
    // ---------------------------------------------------------------

    public function confirm(): void
    {
        $token = Request::get('token');

        if ($this->auth->confirm($token)) {
            Session::flash('success', 'Compte confirmé ! Vous pouvez maintenant vous connecter.');
        } else {
            Session::flash('error', 'Lien de confirmation invalide ou expiré.');
        }

        $this->redirect('/login');
    }

    // ---------------------------------------------------------------
    // Connexion / déconnexion
    // ---------------------------------------------------------------

    public function showLogin(): void
    {
        if ($this->loggedIn()) {
            $this->redirect('/');
        }

        View::render('auth/login', [
            'pageTitle' => 'Connexion — Camagru',
            'old' => [],
            'errors' => [],
        ]);
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $login = Request::post('username');
        $password = Request::post('password');

        $errors = [];
        if ($login === '' || $password === '') {
            $errors[] = 'Renseignez votre nom d\'utilisateur et votre mot de passe.';
        } else {
            $user = $this->users->findByLogin($login);
            if ($user === null || !password_verify($password, $user->passwordHash)) {
                $errors[] = 'Identifiants incorrects.';
            } elseif (!$user->isActive) {
                $errors[] = 'Votre compte n\'est pas encore confirmé : vérifiez votre boîte mail.';
            }
        }

        if ($errors !== []) {
            View::render('auth/login', [
                'pageTitle' => 'Connexion — Camagru',
                'old' => ['username' => $login],
                'errors' => $errors,
            ]);
            return;
        }

        // Anti fixation de session : nouveau identifiant après authentification.
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;

        Session::flash('success', 'Bienvenue, ' . $user->username . ' !');
        $this->redirect('/');
    }

    public function logout(): void
    {
        session_regenerate_id(true);
        unset($_SESSION['user_id'], $_SESSION['username']);

        Session::flash('success', 'Vous êtes déconnecté.');
        $this->redirect('/');
    }

    // ---------------------------------------------------------------
    // Mot de passe oublié
    // ---------------------------------------------------------------

    public function showForgot(): void
    {
        View::render('auth/forgot', [
            'pageTitle' => 'Mot de passe oublié — Camagru',
            'old' => [],
            'errors' => [],
        ]);
    }

    public function forgot(): void
    {
        $this->verifyCsrf();

        $email = Request::post('email');

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors[] = 'Adresse email invalide.';
        }

        if ($errors !== []) {
            View::render('auth/forgot', [
                'pageTitle' => 'Mot de passe oublié — Camagru',
                'old' => ['email' => $email],
                'errors' => $errors,
            ]);
            return;
        }

        $this->auth->sendResetLink($email);

        // Même message que le compte existe ou non (pas d'énumération d'utilisateurs).
        Session::flash('success', 'Si un compte existe pour cette adresse, un email de réinitialisation vient d\'être envoyé.');
        $this->redirect('/login');
    }

    public function showReset(): void
    {
        $token = Request::get('token');
        if ($token === '' || !$this->auth->isResetTokenValid($token)) {
            Session::flash('error', 'Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/forgot');
        }

        View::render('auth/reset', [
            'pageTitle' => 'Nouveau mot de passe — Camagru',
            'token' => $token,
            'errors' => [],
        ]);
    }

    public function reset(): void
    {
        $this->verifyCsrf();

        $token = Request::post('token');
        $password = Request::post('password');
        $passwordConfirm = Request::post('password_confirm');

        $errors = AuthService::validatePassword($password, $passwordConfirm);
        if ($errors !== []) {
            View::render('auth/reset', [
                'pageTitle' => 'Nouveau mot de passe — Camagru',
                'token' => $token,
                'errors' => $errors,
            ]);
            return;
        }

        if (!$this->auth->reset($token, $password)) {
            Session::flash('error', 'Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/forgot');
        }

        Session::flash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');
        $this->redirect('/login');
    }

    // ---------------------------------------------------------------
    // Profil (connecté)
    // ---------------------------------------------------------------

    public function showProfile(): void
    {
        if (!$this->requireLogin('Vous devez être connecté pour accéder à cette page.')) {
            return;
        }

        $user = $this->users->findById((int) $_SESSION['user_id']);
        if ($user === null) {
            $this->redirect('/logout');
        }

        View::render('profile/index', [
            'pageTitle' => 'Mon profil — Camagru',
            'user' => $user,
            'errors' => [],
            'old' => [],
        ]);
    }

    public function updateProfile(): void
    {
        $this->verifyCsrf();
        if (!$this->requireLogin('Vous devez être connecté pour accéder à cette page.')) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $username = Request::post('username');
        $email = Request::post('email');
        $notifyComments = Request::hasPost('notify_comments');
        $currentPassword = Request::post('current_password');
        $newPassword = Request::post('new_password');
        $newPasswordConfirm = Request::post('new_password_confirm');

        $user = $this->users->findById($userId);
        if ($user === null) {
            $this->redirect('/logout');
        }

        $errors = $this->auth->validateProfileUpdate(
            $user,
            $username,
            $email,
            $currentPassword,
            $newPassword,
            $newPasswordConfirm,
        );
        if ($errors !== []) {
            View::render('profile/index', [
                'pageTitle' => 'Mon profil — Camagru',
                'user' => $user,
                'errors' => $errors,
                'old' => ['username' => $username, 'email' => $email, 'notify_comments' => $notifyComments],
            ]);
            return;
        }

        $this->auth->updateProfile($user, $username, $email, $notifyComments, $newPassword !== '' ? $newPassword : null);

        $_SESSION['username'] = $username; // reflété immédiatement dans le header

        Session::flash('success', 'Profil mis à jour.');
        $this->redirect('/profile');
    }
}
