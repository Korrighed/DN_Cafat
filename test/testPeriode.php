<?php
require_once __DIR__ . '/../autoloader.php';

use App\Tags\Periode;

$periode = new Periode();
$xml = $periode->generatePeriode();

echo "=== Test de la classe Periode ===\n\n";
echo $xml;
