<?php
require_once __DIR__ . '/../autoloader.php';

use App\Utils\NommageXml;
use App\Database\Database;

try {
    // Ne pas passer $pdo à genererateName
    $nomFichier = NommageXml::genererateName();
    echo "Nom de fichier généré défaut: $nomFichier\n";

    $nomFichierT4 = NommageXml::genererateName(2022, 'T', 4);
    echo "Nom de fichier pour T4 2022: $nomFichierT4\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
