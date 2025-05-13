<?php
// test_nommage.php

// Autoload des classes (ajustez le chemin si nécessaire)
require_once __DIR__ . '/../autoloader.php';



use App\Utils\NommageXml;

// Titre du test
echo "=== Test de génération des noms de fichiers CAFAT ===\n\n";

try {
    // Test avec les paramètres par défaut
    $nommage = new NommageXml();
    $nomFichier = $nommage->generateName();

    echo "Nom de fichier généré: $nomFichier\n";
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
