<?php

use App\Core\Csrf;
use App\Core\View;
use App\Entities\User;

/** @var User $user Utilisateur connecté. */
/** @var array<string, string|bool> $old Valeurs déjà saisies en cas d'erreur. */
/** @var list<string> $errors Erreurs de validation. */

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);
?>
<div class="auth-card">
    <h1>Mon profil</h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
                <li><?= View::e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/profile">
        <?= Csrf::field() ?>

        <div class="form-field">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" minlength="3" maxlength="50"
                   value="<?= View::e($old['username'] ?? $user->username()) ?>" autocomplete="username" required>
        </div>

        <div class="form-field">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" maxlength="255"
                   value="<?= View::e($old['email'] ?? $user->email()) ?>" autocomplete="email" required>
        </div>

        <?php $notifyChecked = $old['notify_comments'] ?? $user->notifyComments(); ?>
        <div class="form-field checkbox-field">
            <label>
                <input type="checkbox" name="notify_comments" <?= $notifyChecked ? 'checked' : '' ?>>
                M'avertir par email quand quelqu'un commente mes images
            </label>
        </div>

        <h2 class="form-section-title">Changer de mot de passe</h2>
        <p class="muted">Laissez vide pour ne pas changer de mot de passe.</p>

        <div class="form-field">
            <label for="current_password">Mot de passe actuel</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password">
        </div>

        <div class="form-field">
            <label for="new_password">Nouveau mot de passe</label>
            <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password">
        </div>

        <div class="form-field">
            <label for="new_password_confirm">Confirmation du nouveau mot de passe</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm" minlength="8" autocomplete="new-password">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Enregistrer</button>
        </div>
    </form>
</div>

<?php View::render('layout/footer'); ?>
