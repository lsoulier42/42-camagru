<?php

use App\Core\Csrf;
use App\Core\View;

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);
?>
<div class="auth-card">
    <h1>Connexion</h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
                <li><?= View::e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/login">
        <?= Csrf::field() ?>

        <div class="form-field">
            <label for="username">Nom d'utilisateur ou email</label>
            <input type="text" id="username" name="username"
                   value="<?= View::e($old['username'] ?? '') ?>" autocomplete="username" required>
        </div>

        <div class="form-field">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Se connecter</button>
        </div>
    </form>

    <p class="auth-links">
        <a href="/forgot">Mot de passe oublié ?</a><br>
        Pas encore de compte ? <a href="/register">Inscrivez-vous</a>
    </p>
</div>

<?php View::render('layout/footer'); ?>
