<?php

namespace App\Tags;

use App\Database\Database;
use PDO;

/**
 * Classe gérant la génération des balises <assurer> pour la déclaration CAFAT
 */
class Assurer
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

    /**
     * Obtient la date de début du trimestre au format YYYY-MM-DD
     *
     * @return string Date de début du trimestre
     */
    private function getDateDebut(): string
    {
        $moisDebut = ($this->trimestre - 1) * 3 + 1;
        return sprintf("%04d-%02d-01", $this->annee, $moisDebut);
    }

    /**
     * Obtient la date de fin du trimestre au format YYYY-MM-DD
     *
     * @return string Date de fin du trimestre
     */
    private function getDateFin(): string
    {
        $moisFin = $this->trimestre * 3;
        $dernierJour = cal_days_in_month(CAL_GREGORIAN, $moisFin, $this->annee);
        return sprintf("%04d-%02d-%02d", $this->annee, $moisFin, $dernierJour);
    }

    /**
     * Génère les balises <assurer> pour tous les salariés ayant des bulletins dans la période
     * 
     * @return array Tableau associatif [numcafat => xml]
     */
    public function generateAssurer(): array
    {
        $periodes = $this->getPeriodes();
        $periodesStr = implode("', '", $periodes);
        $dateDebut = $this->getDateDebut();
        $dateFin = $this->getDateFin();

        // Obtenir la connexion à la base de données
        $pdo = Database::getInstance()->getConnection();

        // Requête pour générer les balises <assurer> pour chaque salarié
        $query = "
            SELECT 
                s.numcafat,
                UPPER(s.nom) AS nom,
                UPPER(s.prenom) AS prenom,
                s.dnaissance,
                REPLACE(FORMAT(SUM(b.nombre_heures), 2), ',', '.') AS nombre_heures,
                ROUND(SUM(b.brut)) AS remuneration,
                s.dembauche,
                s.drupture
            FROM bulletin b
            INNER JOIN salaries s ON b.salarie_id = s.id
            WHERE b.periode IN ('$periodesStr')
            GROUP BY s.numcafat, s.nom, s.prenom, s.dnaissance, s.dembauche, s.drupture
        ";

        $stmt = $pdo->query($query);
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $numcafat = $row['numcafat'];
            $nom = $row['nom'];
            $prenoms = $row['prenom'];
            $dateNaissance = $row['dnaissance'];
            $nombreHeures = $row['nombre_heures'];
            $remuneration = $row['remuneration'];
            $dateEmbauche = $row['dembauche'];
            $dateRupture = $row['drupture'];

            // Débuter la construction XML
            $xml = "  <assurer>\n";
            $xml .= "    <numero>{$numcafat}</numero>\n";
            $xml .= "    <nom>{$nom}</nom>\n";
            $xml .= "    <prenoms>{$prenoms}</prenoms>\n";
            $xml .= "    <dateNaissance>{$dateNaissance}</dateNaissance>\n";
            $xml .= "    <codeAT>PRINCIPAL</codeAT>\n";
            $xml .= "    <nombreHeures>{$nombreHeures}</nombreHeures>\n";
            $xml .= "    <remuneration>{$remuneration}</remuneration>\n";

            // Ajouter dateEmbauche seulement si elle existe et est dans la période
            if ($dateEmbauche && $dateEmbauche >= $dateDebut && $dateEmbauche <= $dateFin) {
                $xml .= "    <dateEmbauche>{$dateEmbauche}</dateEmbauche>\n";
            }

            // Ajouter dateRupture seulement si elle existe et est dans la période
            if ($dateRupture && $dateRupture >= $dateDebut && $dateRupture <= $dateFin) {
                $xml .= "    <dateRupture>{$dateRupture}</dateRupture>\n";
            }

            $xml .= "  </assurer>";

            $results[$numcafat] = $xml;
        }

        return $results;
    }
}
