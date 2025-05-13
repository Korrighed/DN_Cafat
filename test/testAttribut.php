<?php
// tests/test_attribut.php

require_once __DIR__ . '/../autoloader.php';

use App\Tags\Attribut;

// Test avec les valeurs par défaut
echo "Test de génération de l'attribut XML avec valeurs par défaut:\n";

// Création de l'attribut avec ses valeurs par défaut internes
$attribut = new Attribut();

// Génération du XML avec les valeurs par défaut
$xml = $attribut->generateAttribut();
echo $xml . "\n\n";
