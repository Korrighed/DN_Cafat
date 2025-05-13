<?php

namespace App\Tags;

use App\Database\Database;
use PDO;

class Employeur
{
    /**
     * Génère la partie XML concernant l'employeur
     * 
     * @return string Le bloc XML de l'employeur
     */
    public function genererEmployeur(): string
    {
        try {
            // Utilisation de la classe Database existante
            $pdo = Database::getInstance()->getConnection();

            $query = "SELECT
                        numerocafat,
                        enseigne,
                        ridet,
                        tauxat
                      FROM societe
                      LIMIT 1";

            $stmt = $pdo->prepare($query);
            $stmt->execute();

            $employeur = $stmt->fetch();

            if (!$employeur) {
                throw new \Exception("Aucune information d'employeur trouvée.");
            }

            // Extraction des parties du numéro CAFAT
            $numeroParts = explode('/', $employeur['numerocafat']);
            $numero = $numeroParts[0];
            $suffixe = isset($numeroParts[1]) ? $numeroParts[1] : '';

            // Extraction du RID (partie avant le point dans RIDET)
            $ridParts = explode('.', $employeur['ridet']);
            $rid = $ridParts[0];

            // Construction du XML
            $xml = "<employeur>\n";
            $xml .= "  <numero>{$numero}</numero>\n";
            $xml .= "  <suffixe>{$suffixe}</suffixe>\n";
            $xml .= "  <nom>" . strtoupper($employeur['enseigne']) . "</nom>\n";
            $xml .= "  <rid>{$rid}</rid>\n";
            $xml .= "  <codeCotisation>001</codeCotisation>\n";
            $xml .= "  <tauxATPrincipal>{$employeur['tauxat']}</tauxATPrincipal>\n";
            $xml .= "</employeur>";

            return $xml;
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du XML employeur: " . $e->getMessage());
            return "<employeur>Erreur: Impossible de générer les données</employeur>";
        }
    }
}
