<?php
require_once __DIR__ . '/../autoloader.php';

use App\Tags\Assurer;

$assurer = new Assurer();
$assuresArray = $assurer->generateAssurer();

// Affichage formaté pour vérification
echo "Test de génération des assurés XML:\n";
echo "Nombre d'assurés générés: " . count($assuresArray) . "\n\n";

foreach ($assuresArray as $numcafat => $xml) {
    echo "Pour le numéro CAFAT: $numcafat\n";
    echo $xml . "\n\n";
}
