<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\MailerInterface;
use App\Entities\User;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;

/**
 * Logique métier de l'authentification : règles de validation et flux
 * (inscription + confirmation, mot de passe oublié, profil), hors contrôleur.
 * Les emails sont envoyés via Mailer (maquette MailHog en dev).
 */
final class AuthService
{
    private const int CONFIRM_TTL_SECONDS = 86400; // 24 h
    private const int RESET_TTL_SECONDS = 3600; // 1 h

    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenRepository $tokens,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * Règles d'inscription : nom d'utilisateur, email, mot de passe.
     *
     * @return list<string>
     */
    public function validateRegistration(string $username, string $email, string $password, string $passwordConfirm): array
    {
        return array_merge(
            $this->validateIdentity($username, $email, null),
            self::validatePassword($password, $passwordConfirm)
        );
    }

    /**
     * Niveau de complexité minimal : 8 caractères, au moins une minuscule,
     * une majuscule et un chiffre. Règle pure, sans état.
     *
     * @return list<string>
     */
    public static function validatePassword(string $password, string $confirm): array
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

    /** Crée le compte (inactif), génère le jeton et envoie l'email de confirmation. */
    public function register(string $username, string $email, string $password): void
    {
        $userId = $this->users->create($username, $email, password_hash($password, PASSWORD_DEFAULT));

        $token = $this->tokens->create($userId, 'confirm', self::CONFIRM_TTL_SECONDS);
        $link = Env::get('APP_URL', Env::DEFAULT_APP_URL) . '/confirm?token=' . $token;
        $body = '<p>Bonjour <strong>' . htmlspecialchars($username, ENT_QUOTES) . '</strong>,</p>'
            . '<p>Bienvenue sur Camagru ! Pour activer votre compte, cliquez sur le lien suivant :</p>'
            . '<p><a href="' . $link . '">' . $link . '</a></p>'
            . '<p>Ce lien est valable 24 heures et ne fonctionne qu\'une seule fois.</p>';
        $this->mailer->send($email, 'Confirmez votre compte Camagru', $body);
    }

    /** Active le compte si le jeton est valide (usage unique) ; renvoie true si activé. */
    public function confirm(string $token): bool
    {
        $userId = $this->tokens->findUser('confirm', $token);
        if ($userId === null) {
            return false;
        }

        $this->users->activate($userId);
        $this->tokens->deleteFor($userId, 'confirm'); // usage unique

        return true;
    }

    /**
     * Envoie le lien de réinitialisation si un compte actif existe.
     * Même traitement que le compte existe ou non (pas d'énumération).
     */
    public function sendResetLink(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !$user->isActive) {
            return;
        }

        $token = $this->tokens->create($user->id, 'reset', self::RESET_TTL_SECONDS);
        $link = Env::get('APP_URL', Env::DEFAULT_APP_URL) . '/reset?token=' . $token;
        $body = '<p>Bonjour,</p>'
            . '<p>Vous avez demandé la réinitialisation de votre mot de passe Camagru :</p>'
            . '<p><a href="' . $link . '">' . $link . '</a></p>'
            . '<p>Ce lien est valable 1 heure et ne fonctionne qu\'une seule fois.</p>';
        $this->mailer->send($email, 'Réinitialisation de votre mot de passe', $body);
    }

    /** Le jeton de reset est-il présent, du bon type et non expiré ? */
    public function isResetTokenValid(string $token): bool
    {
        return $this->tokens->findUser('reset', $token) !== null;
    }

    /** Réinitialise le mot de passe ; renvoie true si le jeton était valide. */
    public function reset(string $token, string $password): bool
    {
        $userId = $this->tokens->findUser('reset', $token);
        if ($userId === null) {
            return false;
        }

        $this->users->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        $this->tokens->deleteFor($userId, 'reset'); // usage unique

        return true;
    }

    /**
     * Règles d'édition du profil (édition du mot de passe soumise à la
     * saisie du mot de passe actuel).
     *
     * @return list<string>
     */
    public function validateProfileUpdate(
        User $user,
        string $username,
        string $email,
        string $currentPassword,
        string $newPassword,
        string $newPasswordConfirm,
    ): array {
        $errors = $this->validateIdentity($username, $email, $user->id);

        // Changement de mot de passe : mot de passe actuel obligatoire pour confirmer.
        if ($newPassword !== '' || $newPasswordConfirm !== '') {
            if (!password_verify($currentPassword, $user->passwordHash)) {
                $errors[] = 'Le mot de passe actuel est incorrect.';
            } else {
                $errors = array_merge($errors, self::validatePassword($newPassword, $newPasswordConfirm));
            }
        }

        return $errors;
    }

    /**
     * Règles communes username + email (inscription et édition du profil),
     * en excluant éventuellement le compte courant (édition).
     *
     * @return list<string>
     */
    private function validateIdentity(string $username, string $email, ?int $exceptId): array
    {
        $errors = [];

        if (strlen($username) < 3 || strlen($username) > 50 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $errors[] = 'Le nom d\'utilisateur doit faire 3 à 50 caractères (lettres, chiffres, . _ -).';
        } elseif ($this->users->usernameExists($username, $exceptId)) {
            $errors[] = 'Ce nom d\'utilisateur est déjà pris.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors[] = 'Adresse email invalide.';
        } elseif ($this->users->emailExists($email, $exceptId)) {
            $errors[] = 'Cette adresse email est déjà utilisée.';
        }

        return $errors;
    }

    /** Applique les modifications du profil ; change le mot de passe si demandé. */
    public function updateProfile(User $user, string $username, string $email, bool $notifyComments, ?string $newPassword): void
    {
        $this->users->updateProfile($user->id, $username, $email, $notifyComments);

        if ($newPassword !== null && $newPassword !== '') {
            $this->users->updatePassword($user->id, password_hash($newPassword, PASSWORD_DEFAULT));
        }
    }
}
