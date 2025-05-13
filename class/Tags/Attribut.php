<?php

namespace App\Tags;

use App\Database\Database;
use PDO;

/**
 * Classe gérant la génération de la balise <attributs> pour la déclaration CAFAT
 */
class Attribut
{
    /** @var int L'année de déclaration */
    private int $annee;

    /** @var int Le trimestre de déclaration (1-4) */
    private int $trimestre;

    public function __construct()
    {
        $this->annee = 2022;
        $this->trimestre = 2;
    }

    /**
     * Définit l'année pour la déclaration
     * 
     * @param int $annee L'année à utiliser
     * @return self Pour chaînage de méthodes
     */
    public function setAnnee(int $annee): self
    {
        $this->annee = $annee;
        return $this;
    }

    /**
     * Définit le trimestre pour la déclaration
     * 
     * @param int $trimestre Le trimestre à utiliser (1-4)
     * @return self Pour chaînage de méthodes
     * @throws \Exception Si le trimestre est invalide
     */
    public function setTrimestre(int $trimestre): self
    {
        if ($trimestre < 1 || $trimestre > 4) {
            throw new \Exception("Le trimestre doit être entre 1 et 4");
        }

        $this->trimestre = $trimestre;
        return $this;
    }

    /**
     * Génère la liste des périodes (au format YYYYMM) pour le trimestre configuré
     * 
     * @return array Tableau des périodes au format YYYYMM
     */
    private function getPeriodes(): array
    {
        $moisDebut = ($this->trimestre - 1) * 3 + 1;
        $periodes = [];

        for ($i = 0; $i < 3; $i++) {
            $mois = $moisDebut + $i;
            $periodes[] = sprintf("%04d%02d", $this->annee, $mois);
        }

        return $periodes;
    }

    public function getTrimestre(): int
    {
        return $this->trimestre;
    }



    /**
     * Définit à la fois l'année et le trimestre
     * 
     * @param int $annee L'année à utiliser
     * @param int $trimestre Le trimestre à utiliser (1-4)
     * @return self Pour chaînage de méthodes
     * @throws \Exception Si le trimestre est invalide
     */
    public function setPeriode(int $annee, int $trimestre): self
    {
        $this->setAnnee($annee);
        $this->setTrimestre($trimestre);
        return $this;
    }

    public function getAnnee(): int
    {
        return $this->annee;
    }



    /**
     * Génère les balises <attributs> pour tous les salariés ayant des bulletins dans la période
     * 
     * @return array Tableau associatif [numcafat => xml]
     */
    public function generateAttribut(): array
    {
        $periodes = $this->getPeriodes();
        $periodesStr = implode("', '", $periodes);

        // Obtenir la connexion à la base de données
        $pdo = Database::getInstance()->getConnection();

        // Requête pour obtenir les attributs pour chaque numéro CAFAT de salarié
        $query = "
            SELECT 
                s.id,
                s.numcafat,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 
                        FROM salaries s2
                        WHERE DATE_FORMAT(s2.drupture, '%Y%m') IN ('$periodesStr')
                        AND s2.numcafat = s.numcafat
                    ) THEN 'true'
                    ELSE 'false'
                END AS pasDeReembauche,
                CASE
                    WHEN s.numcafat IS NULL OR s.numcafat = 0 THEN 'true'
                    ELSE 'false'
                END AS pasAssureRemunere
            FROM bulletin b
            INNER JOIN salaries s ON b.salarie_id = s.id
            WHERE b.periode IN ('$periodesStr')
            GROUP BY s.id, s.numcafat
        ";

        $stmt = $pdo->query($query);
        $results = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $numcafat = $row['numcafat'];
            $pasDeReembauche = $row['pasDeReembauche'];
            $pasAssureRemunere = $row['pasAssureRemunere'];

            $xml = <<<XML
            <attributs>
            <complementaire>false</complementaire>
            <contratAlternance>false</contratAlternance>
            <pasAssureRemunere>$pasAssureRemunere</pasAssureRemunere>
            <pasDeReembauche>$pasDeReembauche</pasDeReembauche>
            </attributs>
            XML;


            $key = $numcafat ? $numcafat : "id_" . $id;
            $results[$key] = $xml;
        }

        return $results;
    }
}
