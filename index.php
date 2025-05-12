<?php
require_once __DIR__ . '/autoloader.php';

use App\Database\Database;

$db = Database::getInstance()->getConnection();
echo "<h1>Connexion à la base de données réussie!</h1>";
