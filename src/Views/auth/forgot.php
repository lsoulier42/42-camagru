<?php

use App\Core\Csrf;
use App\Core\View;

View::render('layout/header', ['pageTitle' => $pageTitle ?? 'Camagru']);
?>
<div class="auth-card">
    <h1>Mot de passe oublié</h1>
    <p class="muted">Indiquez votre adresse email : si un compte existe, un lien de
        réinitialisation vous sera envoyé.</p>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
                <li><?= View::e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/forgot">
        <?= Csrf::field() ?>

        <div class="form-field">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" maxlength="255"
                   value="<?= View::e($old['email'] ?? '') ?>" autocomplete="email" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Envoyer le lien</button>
        </div>
    </form>

    <p class="auth-links"><a href="/login">Retour à la connexion</a></p>
</div>

<?php View::render('layout/footer'); ?>
