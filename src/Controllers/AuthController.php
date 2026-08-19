<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;

final class AuthController extends BaseController
{
    private readonly UserRepository $users;
    private readonly TokenRepository $tokens;

    /** Injection manuelle en attendant le conteneur (PR 3 du plan de refacto). */
    public function __construct(?UserRepository $users = null, ?TokenRepository $tokens = null)
    {
        $this->users = $users ?? new UserRepository(Database::pdo());
        $this->tokens = $tokens ?? new TokenRepository(Database::pdo());
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

        $errors = $this->validateRegistration($username, $email, $password, $passwordConfirm);
        if ($errors !== []) {
            View::render('auth/register', [
                'pageTitle' => 'Inscription — Camagru',
                'old' => ['username' => $username, 'email' => $email],
                'errors' => $errors,
            ]);
            return;
        }

        $userId = $this->users->create($username, $email, password_hash($password, PASSWORD_DEFAULT));

        $token = $this->tokens->create($userId, 'confirm', 86400); // 24 h
        $link = Env::get('APP_URL', 'http://localhost:8080') . '/confirm?token=' . $token;
        $body = '<p>Bonjour <strong>' . htmlspecialchars($username, ENT_QUOTES) . '</strong>,</p>'
            . '<p>Bienvenue sur Camagru ! Pour activer votre compte, cliquez sur le lien suivant :</p>'
            . '<p><a href="' . $link . '">' . $link . '</a></p>'
            . '<p>Ce lien est valable 24 heures et ne fonctionne qu\'une seule fois.</p>';
        Mailer::send($email, 'Confirmez votre compte Camagru', $body);

        Session::flash('success', 'Votre compte a été créé. Un email de confirmation vient de vous être envoyé.');
        $this->redirect('/login');
    }

    // ---------------------------------------------------------------
    // Confirmation de compte (lien unique à usage unique)
    // ---------------------------------------------------------------

    public function confirm(): void
    {
        $token = Request::get('token');
        $userId = $token !== '' ? $this->tokens->findUser('confirm', $token) : null;

        if ($userId === null) {
            Session::flash('error', 'Lien de confirmation invalide ou expiré.');
        } else {
            $this->users->activate($userId);
            $this->tokens->deleteFor($userId, 'confirm'); // usage unique
            Session::flash('success', 'Compte confirmé ! Vous pouvez maintenant vous connecter.');
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
            if ($user === null || !password_verify($password, $user->passwordHash())) {
                $errors[] = 'Identifiants incorrects.';
            } elseif (!$user->isActive()) {
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
        $_SESSION['user_id'] = $user->id();
        $_SESSION['username'] = $user->username();

        Session::flash('success', 'Bienvenue, ' . $user->username() . ' !');
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

        $user = $this->users->findByEmail($email);
        if ($user !== null && $user->isActive()) {
            $token = $this->tokens->create($user->id(), 'reset', 3600); // 1 h
            $link = Env::get('APP_URL', 'http://localhost:8080') . '/reset?token=' . $token;
            $body = '<p>Bonjour,</p>'
                . '<p>Vous avez demandé la réinitialisation de votre mot de passe Camagru :</p>'
                . '<p><a href="' . $link . '">' . $link . '</a></p>'
                . '<p>Ce lien est valable 1 heure et ne fonctionne qu\'une seule fois.</p>';
            Mailer::send($email, 'Réinitialisation de votre mot de passe', $body);
        }

        // Même message que le compte existe ou non (pas d'énumération d'utilisateurs).
        Session::flash('success', 'Si un compte existe pour cette adresse, un email de réinitialisation vient d\'être envoyé.');
        $this->redirect('/login');
    }

    public function showReset(): void
    {
        $token = Request::get('token');
        if ($token === '' || $this->tokens->findUser('reset', $token) === null) {
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
        $userId = $this->tokens->findUser('reset', $token);
        if ($userId === null) {
            Session::flash('error', 'Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/forgot');
        }

        $password = Request::post('password');
        $passwordConfirm = Request::post('password_confirm');

        $errors = self::validatePassword($password, $passwordConfirm);
        if ($errors !== []) {
            View::render('auth/reset', [
                'pageTitle' => 'Nouveau mot de passe — Camagru',
                'token' => $token,
                'errors' => $errors,
            ]);
            return;
        }

        $this->users->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        $this->tokens->deleteFor($userId, 'reset'); // usage unique

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

        $errors = [];

        if (strlen($username) < 3 || strlen($username) > 50 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $errors[] = 'Le nom d\'utilisateur doit faire 3 à 50 caractères (lettres, chiffres, . _ -).';
        } elseif ($this->users->usernameExists($username, $userId)) {
            $errors[] = 'Ce nom d\'utilisateur est déjà pris.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors[] = 'Adresse email invalide.';
        } elseif ($this->users->emailExists($email, $userId)) {
            $errors[] = 'Cette adresse email est déjà utilisée.';
        }

        // Changement de mot de passe : mot de passe actuel obligatoire pour confirmer.
        if ($newPassword !== '' || $newPasswordConfirm !== '') {
            if (!password_verify($currentPassword, $user->passwordHash())) {
                $errors[] = 'Le mot de passe actuel est incorrect.';
            } else {
                $errors = array_merge($errors, self::validatePassword($newPassword, $newPasswordConfirm));
            }
        }

        if ($errors !== []) {
            View::render('profile/index', [
                'pageTitle' => 'Mon profil — Camagru',
                'user' => $user,
                'errors' => $errors,
                'old' => ['username' => $username, 'email' => $email, 'notify_comments' => $notifyComments],
            ]);
            return;
        }

        $this->users->updateProfile($userId, $username, $email, $notifyComments);
        if ($newPassword !== '') {
            $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
        }

        $_SESSION['username'] = $username; // reflété immédiatement dans le header

        Session::flash('success', 'Profil mis à jour.');
        $this->redirect('/profile');
    }

    // ---------------------------------------------------------------
    // Validations
    // ---------------------------------------------------------------

    /** @return list<string> */
    private function validateRegistration(string $username, string $email, string $password, string $passwordConfirm): array
    {
        $errors = [];

        if (strlen($username) < 3 || strlen($username) > 50 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $errors[] = 'Le nom d\'utilisateur doit faire 3 à 50 caractères (lettres, chiffres, . _ -).';
        } elseif ($this->users->usernameExists($username)) {
            $errors[] = 'Ce nom d\'utilisateur est déjà pris.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors[] = 'Adresse email invalide.';
        } elseif ($this->users->emailExists($email)) {
            $errors[] = 'Cette adresse email est déjà utilisée.';
        }

        return array_merge($errors, self::validatePassword($password, $passwordConfirm));
    }

    /**
     * Niveau de complexité minimal : 8 caractères, au moins une minuscule,
     * une majuscule et un chiffre.
     *
     * @return list<string>
     */
    private static function validatePassword(string $password, string $confirm): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if ($password !== $confirm) {
            $errors[] = 'La confirmation du mot de passe ne correspond pas.';
        }

        return $errors;
    }

}
