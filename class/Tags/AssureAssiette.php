<?php

namespace App\Tags;

use App\Database\Database;
use App\Utils\PeriodeManager;

/**
 * Classe responsable de générer les balises XML pour les assiettes des assurés
 */
class AssureAssiette
{
    /**
     * Gestionnaire de périodes
     * @var PeriodeManager
     */
    private $periodeManager;

    /**
     * Constructeur
     * 
     * @param PeriodeManager|null 
     */
    public function __construct(?PeriodeManager $periodeManager = null)
    {
        $this->periodeManager = $periodeManager ?? new PeriodeManager();
    }

    /**
     * Définit le gestionnaire de périodes
     * 
     * @param PeriodeManager $periodeManager Gestionnaire de périodes
     * @return self Pour chaînage de méthodes
     */
    public function setPeriodeManager(PeriodeManager $periodeManager): self
    {
        $this->periodeManager = $periodeManager;
        return $this;
    }

    /**
     * Génère le XML pour les assiettes d'un assuré spécifique
     * 
     * @param int $salarieId ID du salarié
     * @param string|null $periode Période spécifique au format YYYYMM (optionnel)
     * @return string Le XML des assiettes
     */
    public function genererXMLPourSalarie(int $salarieId, ?string $periode = null): string
    {
        try {
            // Récupérons les données brutes des assiettes plutôt que de générer le XML via SQL
            $pdo = Database::getInstance()->getConnection();

            $whereClause = "b.salarie_id = :salarie_id";
            $params = [':salarie_id' => $salarieId];

            if ($periode) {
                $whereClause .= " AND b.periode = :periode";
                $params[':periode'] = $periode;
            } else {
                $whereClause .= " AND b.periode BETWEEN :debut AND :fin";
                $params[':debut'] = $this->periodeManager->getPeriodeDebut();
                $params[':fin'] = $this->periodeManager->getPeriodeFin();
            }

            $sql = "
                SELECT 
                    type_identifier AS type,
                    tranche,
                    ROUND(base) AS valeur
                FROM (
                    SELECT 
                        l.base,  
                        CASE 
                            WHEN r.libelle LIKE '%RUAMM%' THEN 'RUAMM'
                            WHEN r.libelle LIKE '%FIAF%' THEN 'FIAF'
                            WHEN r.libelle LIKE '%retraite%' THEN 'RETRAITE'
                            WHEN r.libelle LIKE '%Cho%' THEN 'CHOMAGE'
                            WHEN r.libelle LIKE '%Accident du travail%' THEN 'ATMP'
                            WHEN r.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
                            WHEN r.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
                            WHEN r.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
                            WHEN r.libelle LIKE 'C.R.E.%' THEN 'CRE'
                            WHEN r.libelle LIKE '%CHOMAGE%' THEN 'CHOMAGE'
                        END AS type_identifier,
                        
                        CASE 
                            WHEN r.libelle LIKE '%RUAMM%Tranche 1%' THEN 'TRANCHE_1'
                            WHEN r.libelle LIKE '%RUAMM%Tranche 2%' THEN 'TRANCHE_2'
                            ELSE ''
                        END AS tranche
                    FROM ligne_bulletin l
                    INNER JOIN bulletin b ON l.bulletin_id = b.id
                    INNER JOIN salaries s ON b.salarie_id = s.id
                    INNER JOIN rubrique r ON l.rubrique_id = r.id
                    WHERE 
                        $whereClause
                        AND (
                            r.libelle LIKE '%RUAMM%' OR 
                            r.libelle LIKE 'C.R.E.%' OR 
                            r.libelle LIKE '%retraite%' OR
                            r.libelle LIKE '%FIAF%' OR
                            r.libelle LIKE '%Accident du travail%' OR
                            r.libelle LIKE '%FDS Financement Dialogue Social%' OR
                            r.libelle LIKE '%Formation Professionnelle continue%' OR
                            r.libelle LIKE '%Fond Social de l%Habitat%' OR
                            r.libelle LIKE '%CHOMAGE%'
                        )
                ) AS assiette_types
                WHERE type_identifier IS NOT NULL AND base IS NOT NULL AND base > 0
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $assiettes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($assiettes)) {
                return '';
            }

            // Générer le XML avec l'indentation appropriée
            $xml = "\t\t\t\t<assiettes>\n";

            foreach ($assiettes as $assiette) {
                $xml .= "\t\t\t\t\t<assiette>\n";
                $xml .= "\t\t\t\t\t\t<type>{$assiette['type']}</type>\n";

                if (!empty($assiette['tranche'])) {
                    $xml .= "\t\t\t\t\t\t<tranche>{$assiette['tranche']}</tranche>\n";
                }

                $xml .= "\t\t\t\t\t\t<valeur>{$assiette['valeur']}</valeur>\n";
                $xml .= "\t\t\t\t\t</assiette>\n";
            }

            $xml .= "\t\t\t\t\t</assiettes>\n";

            return $xml;
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du XML des assiettes: " . $e->getMessage());
            return '';
        }
    }
}
