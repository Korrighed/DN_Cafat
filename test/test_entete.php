<?php
require_once __DIR__ . '/../autoloader.php';

use App\Tags\Entete;

$entete = new Entete();
$xml = $entete->generate();

// Affichage formaté pour vérification
echo "Test de génération d'entête XML:\n";
echo $xml;
