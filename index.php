<?php
require_once 'class/Database.php';

$db = Database::getInstance();
$connection = $db->getConnection();


echo "<h1>Connexion à la base de données réussie!</h1>";
