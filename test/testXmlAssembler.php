<?php

require_once __DIR__ . '/../autoloader.php';

use App\Tags\Entete;
use App\Tags\Periode;

try {
    $entete = new Entete();
    $periode = new Periode();

    $xmlOutput = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
    //Balise globale
    $xmlOutput .= "<doc>\n";

    //Balise entete
    $xmlOutput .= $entete->generate() . "\n";

    // Ajouter le corps avec la période
    $xmlOutput .= " <corps>\n";

    //Balise periode
    $xmlOutput .= $periode->generate() . "\n";


    $xmlOutput .= " </corps>\n";

    // Fermer le document
    $xmlOutput .= "</doc>";


    echo "\n\nFichier XML généré avec succès\n";
    echo "$xmlOutput";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
