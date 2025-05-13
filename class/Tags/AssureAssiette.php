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

            $sql = $this->construireRequete($whereClause);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetch();
            return $result['xml_output'] ?? '';
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du XML des assiettes: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Construit la requête SQL pour extraire les données des assiettes
     * 
     * @param string $whereClause Clause WHERE supplémentaire
     * @return string Requête SQL complète
     */
    private function construireRequete(string $whereClause): string
    {
        // Le reste de la méthode reste inchangé
        return "WITH assiette_types AS (
            SELECT 
                l.id,
                l.bulletin_id,
                b.salarie_id,
                s.numcafat,
                b.periode,
                l.base,  
                r.libelle,
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
                
                -- Détermine la tranche pour RUAMM
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
        )

        SELECT 
            at.numcafat,
            at.periode,
            CONCAT(
                CASE
                    WHEN COUNT(CASE WHEN at.type_identifier IS NOT NULL AND at.base IS NOT NULL AND at.base > 0 THEN 1 ELSE NULL END) > 0 THEN
                        CONCAT('    <assiettes>',
                            GROUP_CONCAT(
                                CASE WHEN at.type_identifier IS NOT NULL AND at.base IS NOT NULL AND at.base > 0 THEN
                                    CONCAT('
        <assiette>
            <type>', at.type_identifier, '</type>',
                                    CASE WHEN at.tranche != '' THEN CONCAT('
            <tranche>', at.tranche, '</tranche>') ELSE '' END,
                                    '
            <valeur>', LEFT(ROUND(at.base), 18), '</valeur>
        </assiette>')
                                ELSE '' END
                            SEPARATOR ''), '
    </assiettes>\n')
                    ELSE ''
                END
            ) AS xml_output
        FROM assiette_types at
        GROUP BY at.numcafat, at.periode";
    }
}
