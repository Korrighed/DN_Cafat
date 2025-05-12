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
    public function generate(): string
    {
        try {
            $stmt = $this->pdo->query("SELECT enseigne FROM societe LIMIT 1");
            $societe = $stmt->fetch();
            $enseigne = $societe ? strtoupper($societe['enseigne']) : 'ENTREPRISE';
            $dateGeneration = date('Y-m-d\TH:i:s');

            // Construction du XML
            $xml = <<<XML
    <entete>
        <type>DN</type>
        <version>VERSION_2_0</version>
        <emetteur>{$enseigne}</emetteur>
        <dateGeneration>{$dateGeneration}</dateGeneration>
        <logiciel>
            <editeur>WEBDEV-2025</editeur>
            <nom>DECLARATION-NOMINATIVE-MANAGER</nom>
            <version>1</version>
            <dateVersion>2023-05-15</dateVersion>
        </logiciel>
    </entete>
XML;
            return $xml;
        } catch (PDOException $e) {
            // Gestion des erreurs
            error_log("Erreur lors de la génération de l'entête XML : " . $e->getMessage());
            return "<entete>Erreur lors de la génération</entete>";
        }
    }
}
