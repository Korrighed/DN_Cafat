<?php
require_once __DIR__ . '/../autoloader.php';

use App\Utils\NommageXml;
use App\Database\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    $nomFichier = NommageXml::genererateName($pdo);
    echo "Nom de fichier généré défaut: $nomFichier\n";

    $nomFichierT4 = NommageXml::genererateName($pdo, 2022, 'T', 4);
    echo "Nom de fichier pour T4 2023: $nomFichierT4\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}