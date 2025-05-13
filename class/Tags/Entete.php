<?php

namespace App\Tags;

use App\Database\Database;
use PDOException;

/**
 * Gère la génération de la section <entete> du XML CAFAT
 */
class Entete
{
    private $pdo;

    /**
     * Initialise la connexion à la BD
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Génère le fragment XML de l'entête
     * @return string XML formaté
     */
    public function generateEnTete(): string
    {
        try {
            $stmt = $this->pdo->query("SELECT enseigne FROM societe LIMIT 1");
            $societe = $stmt->fetch();
            $enseigne = $societe ? strtoupper($societe['enseigne']) : 'ENTREPRISE';
            $dateGeneration = date('Y-m-d\TH:i:s');

            // Construction du XML avec indentation explicite
            $xml = "\t<entete>\n";
            $xml .= "\t\t<type>DN</type>\n";
            $xml .= "\t\t<version>VERSION_2_0</version>\n";
            $xml .= "\t\t<emetteur>{$enseigne}</emetteur>\n";
            $xml .= "\t\t<dateGeneration>{$dateGeneration}</dateGeneration>\n";
            $xml .= "\t\t<logiciel>\n";
            $xml .= "\t\t\t<editeur>WEBDEV-2025</editeur>\n";
            $xml .= "\t\t\t<nom>DECLARATION-NOMINATIVE-MANAGER</nom>\n";
            $xml .= "\t\t\t<version>1</version>\n";
            $xml .= "\t\t\t<dateVersion>2023-05-15</dateVersion>\n";
            $xml .= "\t\t</logiciel>\n";
            $xml .= "\t</entete>\n\n";

            return $xml;
        } catch (PDOException $e) {
            // Gestion des erreurs
            error_log("Erreur lors de la génération de l'entête XML : " . $e->getMessage());
            return "\t<entete>Erreur lors de la génération</entete>\n";
        }
    }
}
