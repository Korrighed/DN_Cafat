<?php

require_once dirname(__DIR__) . '/autoloader.php';

use App\Utils\XMLValidator;

// Chemin vers votre fichier XSD
$xsdPath = __DIR__ . '/app-validator-2.0.xsd';

// Créez une instance du validateur
$validator = new XMLValidator($xsdPath);

// Permettre de spécifier un fichier à tester via argument en ligne de commande
$xmlPath = isset($argv[1]) ? $argv[1] : __DIR__ . '/XSD.xml';

// Si le répertoire 'declarations' existe, listez les fichiers XML disponibles
$declarationsDir = dirname(__DIR__) . '/declarations';
if (!file_exists($xmlPath) && is_dir($declarationsDir)) {
    $xmlFiles = glob($declarationsDir . '/*.xml');

    if (empty($xmlFiles)) {
        echo "Aucun fichier XML trouvé dans le dossier 'declarations'.\n";
        echo "Usage: php testXMLValidator.php [chemin_vers_fichier_xml]\n";
        exit(1);
    }

    echo "Sélectionnez un fichier XML à valider:\n";
    foreach ($xmlFiles as $index => $file) {
        echo ($index + 1) . ": " . basename($file) . "\n";
    }

    echo "Votre choix (1-" . count($xmlFiles) . "): ";
    $handle = fopen("php://stdin", "r");
    $choice = (int) trim(fgets($handle));
    fclose($handle);

    if ($choice < 1 || $choice > count($xmlFiles)) {
        echo "Choix invalide.\n";
        exit(1);
    }

    $xmlPath = $xmlFiles[$choice - 1];
}

if (!file_exists($xmlPath)) {
    echo "Le fichier XML '$xmlPath' n'existe pas.\n";
    echo "Usage: php testXMLValidator.php [chemin_vers_fichier_xml]\n";
    exit(1);
}

echo "Validation du fichier: " . $xmlPath . "\n";
echo "Utilisation du schéma XSD: " . $xsdPath . "\n\n";

$result = $validator->validateXmlFile($xmlPath);

echo "Résultat: " . ($result['success'] ? "✅ VALIDE" : "❌ NON VALIDE") . "\n\n";

if (!$result['success']) {
    echo "Erreurs détectées:\n";
    foreach ($result['messages'] as $message) {
        echo "- " . $message . "\n";
    }
} else {
    echo "Le document XML est conforme au schéma XSD.\n";
}
