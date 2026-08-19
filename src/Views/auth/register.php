<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, string> $old Valeurs déjà saisies en cas d'erreur. */
/** @var list<string> $errors Erreurs de validation. */

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);
?>
<div class="auth-card">
    <h1>Inscription</h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
                <li><?= View::e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/register">
        <?= Csrf::field() ?>

        <div class="form-field">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" minlength="3" maxlength="50"
                   value="<?= View::e($old['username'] ?? '') ?>" autocomplete="username" required>
        </div>

        <div class="form-field">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" maxlength="255"
                   value="<?= View::e($old['email'] ?? '') ?>" autocomplete="email" required>
        </div>

        <div class="form-field">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
            <p class="form-hint">8 caractères minimum, avec au moins une minuscule, une majuscule et un chiffre.</p>
        </div>

        <div class="form-field">
            <label for="password_confirm">Confirmation du mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" autocomplete="new-password" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Créer mon compte</button>
        </div>
    </form>

    <p class="auth-links">Déjà inscrit ? <a href="/login">Connectez-vous</a></p>
</div>

<?php View::render('layout/footer'); ?>
