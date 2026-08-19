<?php

declare(strict_types=1);

use App\Core\Env;

require dirname(__DIR__) . '/vendor/autoload.php';

define('APP_ROOT', dirname(__DIR__));

// Priorité : variables d'environnement réelles (CI) puis .env local (dev).
Env::load(APP_ROOT . '/.env');
foreach ([
    'APP_ENV', 'APP_URL',
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_ROOT_PASS',
] as $key) {
    $value = getenv($key);
    if ($value !== false) {
        Env::set($key, $value);
    }
}

// Base dédiée aux tests : jamais la base de développement.
// Créée (idempotent) avec le compte root, puis droits accordés au compte applicatif.
$testDb = (string) (getenv('DB_NAME_TEST') ?: 'camagru_test');
$serverDsn = sprintf(
    'mysql:host=%s;port=%s;charset=utf8mb4',
    Env::get('DB_HOST', 'db'),
    Env::get('DB_PORT', '3306')
);

$root = new PDO($serverDsn, 'root', Env::get('DB_ROOT_PASS', 'root'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$root->exec("CREATE DATABASE IF NOT EXISTS `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$root->exec("GRANT ALL PRIVILEGES ON `{$testDb}`.* TO '" . Env::get('DB_USER', 'camagru') . "'@'%'");
$root->exec('USE `' . $testDb . '`');

$schema = (string) file_get_contents(APP_ROOT . '/database/schema.sql');
foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
    $root->exec($statement);
}
unset($root);

// L'application (Database::pdo) pointe désormais vers la base de test.
Env::set('DB_NAME', $testDb);
