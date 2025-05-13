<?php
// test-decompte.php

require_once __DIR__ . '/../autoloader.php';

use App\Tags\Decompte;
use App\Utils\PeriodeManager;

// Créer un gestionnaire de période avec les valeurs par défaut (T2 2022)
$periodeManager = new PeriodeManager();

// Créer et configurer le décompte avec le gestionnaire de période
$decompte = new Decompte($periodeManager);

// Générer le XML du décompte
try {
    $xmlDecompte = $decompte->generateDecompte();
    echo "XML décompte généré avec succès :\n\n";
    echo $xmlDecompte;
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
