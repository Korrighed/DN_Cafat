<?php
require_once __DIR__ . '/../autoloader.php';

use App\Utils\AssembleurXML;

// Créer un assembleur XML
$assembleur = new AssembleurXML();

// Générer la déclaration complète
$xmlComplet = $assembleur->genererDeclarationComplete();

// Afficher le résultat
echo $xmlComplet;
