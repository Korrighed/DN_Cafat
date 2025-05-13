<?php
require_once __DIR__ . '/../autoloader.php';

use App\Tags\Attribut;

$attribut = new Attribut();
$attributsArray  = $attribut->generateAttribut();

// Affichage formaté pour vérification
echo "Test de génération de l'attribut XML:\n";
echo "Nombre d'attributs générés: " . count($attributsArray) . "\n\n";

foreach ($attributsArray as $numcafat => $xml) {
    echo "Pour le numéro CAFAT: $numcafat\n";
    echo $xml . "\n\n";
}
