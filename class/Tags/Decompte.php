<?php

namespace App\Tags;

use App\Database\Database;
use App\Utils\PeriodeManager;

/**
 * Classe pour générer la section decompte du document XML CAFAT
 */
class Decompte
{

    /**
     * @var \PDO Instance de la connexion PDO
     */
    private $pdo;

    /**
     * @var PeriodeManager Gestionnaire de périodes
     */
    private $periodeManager;

    /**
     * Constructeur
     * 
     * @param PeriodeManager $periodeManager Gestionnaire de périodes
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->periodeManager = new PeriodeManager();
    }

    /**
     * Génère la balise decompte XML
     * 
     * @return string Le XML généré pour la section decompte
     * @throws \RuntimeException Si une erreur survient lors de la génération
     */
    public function generateDecompte(): string
    {
        $cotisations = $this->getCotisations();
        $totalCotisations = $this->calculerTotalCotisations($cotisations);

        // Formater le XML
        return $this->formaterDecompteXML($cotisations, $totalCotisations);
    }

    /**
     * Récupère les cotisations depuis la base de données
     * 
     * @return array Liste des cotisations
     */
    private function getCotisations(): array
    {
        $periodeDebut = $this->periodeManager->getPeriodeDebut();
        $periodeFin = $this->periodeManager->getPeriodeFin();

        if (empty($periodeDebut) || empty($periodeFin)) {
            return [];
        }

        // Générer la liste des périodes (mois) entre début et fin
        $startDate = new \DateTime($this->periodeManager->getDateDebut());
        $endDate = new \DateTime($this->periodeManager->getDateFin());
        $periodes = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $periodes[] = $currentDate->format('Ym');
            $currentDate->modify('+1 month');
        }

        if (empty($periodes)) {
            return [];
        }


        $periodesFormatted = implode("', '", $periodes);

        $sql = "WITH cotisation_types AS (
            SELECT 
                l.id,
                l.bulletin_id,
                l.base,
                r.libelle,
                r.taux_patronal AS taux_ref_patronal,
                CASE 
                    WHEN r.libelle LIKE '%RUAMM%' THEN 'RUAMM'
                    WHEN r.libelle LIKE '%FIAF%' THEN 'FIAF'
                    WHEN r.libelle LIKE '%retraite%' THEN 'RETRAITE'
                    WHEN r.libelle LIKE '%Cho%' THEN 'CHOMAGE'
                    WHEN r.libelle LIKE 'C.R.E.%' THEN 'CRE'
                    WHEN r.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
                    WHEN r.libelle LIKE '%Formation%' THEN 'FORMATION_PROFESSIONNELLE'
                    WHEN r.libelle LIKE '%Accident du travail%' THEN 'ATMP_PRINCIPAL'
                    WHEN r.libelle LIKE '%CS%' THEN 'CSS'
                    WHEN r.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
                END AS type_cotisation,
                CASE 
                    WHEN r.libelle LIKE '%RUAMM%Tranche 1%' THEN 'TRANCHE_1'
                    WHEN r.libelle LIKE '%RUAMM%Tranche 2%' THEN 'TRANCHE_2'
                    ELSE ''
                END AS tranche
            FROM ligne_bulletin l
            INNER JOIN bulletin b ON l.bulletin_id = b.id
            INNER JOIN rubrique r ON l.rubrique_id = r.id
            WHERE 
                b.periode IN ('$periodesFormatted')
                AND (
                    r.libelle LIKE '%RUAMM%' OR 
                    r.libelle LIKE 'C.R.E.%' OR 
                    r.libelle LIKE '%retraite%' OR
                    r.libelle LIKE '%FIAF%' OR
                    r.libelle LIKE '%Accident du travail%' OR
                    r.libelle LIKE '%FDS Financement Dialogue Social%' OR
                    r.libelle LIKE '%Formation Professionnelle continue%' OR
                    r.libelle LIKE '%Fond Social de l%Habitat%' OR
                    r.libelle LIKE '%CHOMAGE%' OR
                    r.libelle LIKE '%CS%'
                )
        )
        SELECT 
            ct.type_cotisation,
            ct.tranche,
            SUM(ct.base) AS assiette,
            SUM(CASE
                WHEN ct.libelle LIKE '%Accident du travail%' THEN ct.base * s.tauxat / 100
                ELSE ct.base * ct.taux_ref_patronal / 100
            END) AS valeur
        FROM cotisation_types ct
        INNER JOIN bulletin b ON ct.bulletin_id = b.id
        INNER JOIN societe s ON 1=1
        WHERE 
            ct.type_cotisation IS NOT NULL
            AND ct.base > 0
        GROUP BY ct.type_cotisation, ct.tranche
        HAVING SUM(CASE
                WHEN ct.libelle LIKE '%Accident du travail%' THEN ct.base * s.tauxat / 100
                ELSE ct.base * ct.taux_ref_patronal / 100
            END) > 0";

        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Erreur lors de la récupération des cotisations: " . $e->getMessage());
        }
    }

    /**
     * Calcule le total des cotisations
     * 
     * @param array $cotisations Liste des cotisations
     * @return float Total des cotisations
     */
    private function calculerTotalCotisations(array $cotisations): float
    {
        $total = 0;
        foreach ($cotisations as $cotisation) {
            $total += (float)$cotisation['valeur'];
        }
        return $total;
    }

    /**
     * Formate le XML du décompte
     * 
     * @param array $cotisations Liste des cotisations
     * @param float $totalCotisations Total des cotisations
     * @return string XML formaté
     */
    private function formaterDecompteXML(array $cotisations, float $totalCotisations): string
    {
        $totalCotisations = round($totalCotisations);

        $xml = "\t\t<decompte>\n";
        $xml .= "\t\t\t<cotisations>\n";

        foreach ($cotisations as $cotisation) {
            $xml .= "\t\t\t\t<cotisation>\n";
            $xml .= "\t\t\t\t\t<type>{$cotisation['type_cotisation']}</type>\n";

            if (!empty($cotisation['tranche'])) {
                $xml .= "\t\t\t\t\t<tranche>{$cotisation['tranche']}</tranche>\n";
            }

            $xml .= "\t\t\t\t\t<assiette>" . round($cotisation['assiette']) . "</assiette>\n";
            $xml .= "\t\t\t\t\t<valeur>" . round($cotisation['valeur']) . "</valeur>\n";
            $xml .= "\t\t\t\t</cotisation>\n";
        }

        $xml .= "\t\t\t</cotisations>\n\n";
        $xml .= "\t\t\t<totalCotisations>{$totalCotisations}</totalCotisations>\n\n";
        $xml .= "\t\t\t<deductions></deductions>\n";
        $xml .= "\t\t\t<montantAPayer>{$totalCotisations}</montantAPayer>\n";
        $xml .= "\t\t</decompte>\n";

        return $xml;
    }
}
