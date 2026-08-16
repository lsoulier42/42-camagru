<?php

use App\Core\Env;

$dbStatus = $dbStatus ?? null;
?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>© <?= date('Y') ?> Camagru — projet 42</span>
        <?php if (Env::get('APP_ENV', 'dev') === 'dev' && $dbStatus !== null): ?>
            <span class="db-status db-status--<?= $dbStatus === 'ok' ? 'ok' : 'error' ?>">
                Base de données : <?= $dbStatus === 'ok' ? 'connectée' : 'indisponible' ?>
            </span>
        <?php endif; ?>
    </div>
</footer>
</body>
</html>
