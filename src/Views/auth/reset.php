<?php

use App\Core\Csrf;
use App\Core\View;

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);
?>
<div class="auth-card">
    <h1>Nouveau mot de passe</h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
                <li><?= View::e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/reset">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= View::e($token) ?>">

        <div class="form-field">
            <label for="password">Nouveau mot de passe</label>
            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
            <p class="form-hint">8 caractères minimum, avec au moins une minuscule, une majuscule et un chiffre.</p>
        </div>

        <div class="form-field">
            <label for="password_confirm">Confirmation du nouveau mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" autocomplete="new-password" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Mettre à jour mon mot de passe</button>
        </div>
    </form>
</div>

<?php View::render('layout/footer'); ?>
