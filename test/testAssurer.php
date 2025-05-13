<?php
// test/testAssure.php
require_once __DIR__ . '/../autoloader.php';

use App\Tags\Assure;

echo "Test: Génération des assurés avec la période par défaut (T2 2022)\n";
$assure = new Assure();
$assuresArray = $assure->genererAssure();

// Affichage formaté pour vérification
echo "Nombre d'assurés générés: " . count($assuresArray) . "\n\n";

foreach ($assuresArray as $numcafat => $xml) {
    echo "Pour le numéro CAFAT: $numcafat\n";
    echo $xml . "\n\n";
}
