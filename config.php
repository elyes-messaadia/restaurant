<?php
// config.php

define('DB_HOST', '136.243.172.164');
define('DB_PORT', '30005');
define('DB_NAME', 'cdpi_groupe2_dev3');
define('DB_USER', 'cdpi_groupe2_dev3');
define('DB_PASS', 'cdpi_groupe2_dev3');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connexion échouée : ' . $e->getMessage());
}
?>
