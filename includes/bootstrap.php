<?php
declare(strict_types=1);
$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Configuration absente. Copiez config.example.php vers config.php et renseignez la base de données.');
}
$config = require $configFile;
if (!empty($config['https_only']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>!empty($config['https_only'])]);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
try {
    $pdo = new PDO(
        'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]
    );
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Nintage database connection failed: '.$e->getMessage());
    exit('Connexion à la base impossible. Vérifiez config.php et les identifiants MySQL.');
}
require_once __DIR__.'/functions.php';

// Tags standards + nouvelles catégories, ajoutés sans écraser les données existantes.
try {
    $pdo->exec("INSERT IGNORE INTO tags (name, slug) VALUES ('Difficile','difficile'),('Classique','classique'),('Mauvais','mauvais'),('Sous-coté','sous-cote'),('Très difficile','tres-difficile'),('Facile','facile')");
} catch (Throwable $e) { error_log('Nintage tag seed failed: '.$e->getMessage()); }
