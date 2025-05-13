<?php

require_once __DIR__ . '/../autoloader.php';

use App\Tags\Employeur;

// Test de la classe EmployeurXML
$employeurXML = new Employeur();
$xmlOutput = $employeurXML->genererEmployeur();

// Affichage du résultat
echo "Test de la génération du XML Employeur";
echo $xmlOutput;
