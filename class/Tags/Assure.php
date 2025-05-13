<?php
// class/Tags/Assure.php

namespace App\Tags;

use App\Database\Database;
use App\Tags\AssureAssiette;
use App\Utils\PeriodeManager;

/**
 * Classe responsable de générer le XML pour la section assurés
 */
class Assure
{
    /**
     * @var PeriodeManager Gestionnaire de période
     */
    private $periodeManager;

    /**
     * @var AssureAssiette Générateur d'assiettes pour les assurés
     */
    private $assureAssiette;

    /**
     * Constructeur
     * 
     * @param PeriodeManager|null $periodeManager Gestionnaire de période (optionnel)
     */
    public function __construct(?PeriodeManager $periodeManager = null)
    {
        $this->periodeManager = $periodeManager ?? new PeriodeManager();
        $this->assureAssiette = new AssureAssiette();
        $this->assureAssiette->setPeriodeManager($this->periodeManager);
    }

    /**
     * Définit le gestionnaire de période
     *
     * @param PeriodeManager $periodeManager Gestionnaire de période
     * @return self Pour chaînage de méthodes
     */
    public function setPeriodeManager(PeriodeManager $periodeManager): self
    {
        $this->periodeManager = $periodeManager;
        $this->assureAssiette->setPeriodeManager($periodeManager);
        return $this;
    }

    /**
     * Génère les balises XML pour tous les assurés
     * 
     * @return array Tableau associatif [numcafat => xml_assure]
     */
    public function genererAssure(): array
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            // Récupération des informations de base pour tous les assurés avec bulletins sur la période
            $query = "SELECT 
                        s.id as salarieId,
                        s.numcafat as numero,
                        UPPER(s.nom) as nom,
                        UPPER(s.prenom) as prenom,
                        s.dnaissance as dateNaissance,
                        s.dembauche as dateEmbauche,
                        s.drupture as dateRupture,
                        ROUND(SUM(b.brut)) as brut,
                        REPLACE(FORMAT(SUM(b.nombre_heures), 2), ',', '.') as nombreHeures
                    FROM salaries s
                    INNER JOIN bulletin b ON s.id = b.salarie_id
                    WHERE b.periode BETWEEN :debut AND :fin
                    GROUP BY s.id, s.numcafat, s.nom, s.prenom, s.dnaissance, s.dembauche, s.drupture";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':debut' => $this->periodeManager->getPeriodeDebut(),
                ':fin' => $this->periodeManager->getPeriodeFin()
            ]);

            $assures = [];
            while ($assure = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                // Formatage XML pour chaque assuré
                $xml = $this->formaterAssureXML($assure);
                if (!empty($xml)) {
                    $assures[$assure['numero']] = $xml;
                }
            }

            return $assures;
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du XML pour les assurés: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Formate les données d'un assuré en XML
     * 
     * @param array $assure Données de l'assuré
     * @return string XML formaté
     */
    private function formaterAssureXML(array $assure): string
    {
        // Génération du XML
        $xml = "<assure>\n";
        $xml .= "    <numero>{$assure['numero']}</numero>\n";
        $xml .= "    <nom>{$assure['nom']}</nom>\n";
        $xml .= "    <prenoms>{$assure['prenom']}</prenoms>\n";
        $xml .= "    <dateNaissance>" . date('Y-m-d', strtotime($assure['dateNaissance'])) . "</dateNaissance>\n";
        $xml .= "    <codeAT>PRINCIPAL</codeAT\n";
        $xml .= "    <nombreHeures>{$assure['nombreHeures']}</nombreHeures>\n";
        $xml .= "    <remuneration>{$assure['brut']}</remuneration>\n";

        // Génération des assiettes pour cet assuré
        $xml .= $this->assureAssiette->genererXMLPourSalarie($assure['salarieId']);

        // Ajout des dates d'embauche/sortie si elles se situent dans la période
        $dateEmbauche = $assure['dateEmbauche'] ? date('Y-m-d', strtotime($assure['dateEmbauche'])) : null;
        $dateRupture = $assure['dateRupture'] ? date('Y-m-d', strtotime($assure['dateRupture'])) : null;

        $dateDebut = $this->periodeManager->getDateDebut();
        $dateFin = $this->periodeManager->getDateFin();

        if ($dateEmbauche && $dateEmbauche >= $dateDebut && $dateEmbauche <= $dateFin) {
            $xml .= "    <dateEmbauche>{$dateEmbauche}</dateEmbauche>\n";
        }

        if ($dateRupture && $dateRupture >= $dateDebut && $dateRupture <= $dateFin) {
            $xml .= "    <dateRupture>{$dateRupture}</dateRupture>\n";
        }

        $xml .= "</assure>";

        return $xml;
    }
}
