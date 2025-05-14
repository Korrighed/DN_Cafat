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
     * Plafonds pour chaque type de cotisation
     * @var array
     */
    private const PLAFONDS = [
        'RUAMM' => 548600,
        'CHOMAGE' => 390900,
        'ATMP' => 390900,
        'FSH' => 329700,
        'FIAF' => 548600,
        'RETRAITE_AGIRC' => 468377,
        'RETRAITE_CEG' => 468377,
    ];

    /**
     * Taux de cotisation pour chaque type
     * @var array
     */
    private const TAUX = [
        'RUAMM' => [
            'TRANCHE_1' => 0.0285,
            'TRANCHE_2' => 0.0125,
        ],
        'CHOMAGE' => 0.0069,
        'ATMP' => 0,
        'FSH' => 0,
        'FORMATION_PROFESSIONNELLE' => 0,
        'FIAF' => 0,
        'RETRAITE_AGIRC' => 0.0315,
        'RETRAITE_CEG' => 0.0086,
    ];

    /**
     * Mapping des types d'identifiants vers les types XML
     * @var array
     */
    private const TYPE_MAPPING = [
        'RETRAITE_AGIRC' => 'RETRAITE',
        'RETRAITE_CEG' => 'RETRAITE',
    ];

    /**
     * Constructeur
     * 
     * @param PeriodeManager|null $periodeManager
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

            $sql = "
                SELECT 
                    l.id,
                    l.bulletin_id,
                    b.salarie_id,
                    l.base,  
                    r.libelle,
                    CASE 
                        WHEN r.libelle LIKE '%RUAMM%' THEN 'RUAMM'
                        WHEN r.libelle LIKE '%FIAF%' THEN 'FIAF'
                        WHEN r.libelle LIKE '%retraite Agirc%' THEN 'RETRAITE_AGIRC'
                        WHEN r.libelle LIKE '%retraite CEG%' THEN 'RETRAITE_CEG'
                        WHEN r.libelle LIKE '%Cho%' THEN 'CHOMAGE'
                        WHEN r.libelle LIKE '%Accident du travail%' THEN 'ATMP'
                        WHEN r.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
                        WHEN r.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
                        WHEN r.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
                        WHEN r.libelle LIKE 'C.R.E.%' THEN 'CRE'
                    END AS type_identifier
                FROM ligne_bulletin l
                INNER JOIN bulletin b ON l.bulletin_id = b.id
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
                        r.libelle LIKE '%CHOMAGE%' OR
                        r.libelle LIKE '%Cho%'
                    )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $assiettesData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $assiettesPrepared = $this->preparerAssiettes($assiettesData);

            if (empty($assiettesPrepared)) {
                return '';
            }

            $xml = "\t\t\t<assiettes>\n";

            foreach ($assiettesPrepared as $assiette) {
                $xml .= "\t\t\t\t<assiette>\n";
                $xml .= "\t\t\t\t\t<type>{$assiette['type']}</type>\n";

                if (isset($assiette['tranche']) && !empty($assiette['tranche'])) {
                    $xml .= "\t\t\t\t\t<tranche>{$assiette['tranche']}</tranche>\n";
                }

                // Formatage de la valeur sans décimales
                $xml .= "\t\t\t\t\t<valeur>" . (int)$assiette['valeur'] . "</valeur>\n";
                $xml .= "\t\t\t\t</assiette>\n";
            }

            $xml .= "\t\t\t</assiettes>\n";

            return $xml;
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du XML des assiettes: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Prépare les données d'assiettes pour la génération XML avec calcul des cotisations
     * 
     * @param array $assiettesBrutes Données brutes des assiettes
     * @return array Assiettes préparées pour la génération XML
     */
    private function preparerAssiettes(array $assiettesBrutes): array
    {
        $assiettesPrepared = [];
        $assiettesByType = [];

        // Regrouper les assiettes par type pour obtenir la base totale de chaque type
        foreach ($assiettesBrutes as $assiette) {
            if (
                isset($assiette['type_identifier']) && $assiette['type_identifier'] !== null &&
                isset($assiette['base']) && $assiette['base'] > 0
            ) {
                $type = $assiette['type_identifier'];

                if (!isset($assiettesByType[$type])) {
                    $assiettesByType[$type] = 0;
                }

                $assiettesByType[$type] += (float)$assiette['base'];
            }
        }

        // Appliquer les plafonds et taux spécifiques pour chaque type
        foreach ($assiettesByType as $typeId => $base) {
            $base = (float)$base;

            $typeXML = isset(self::TYPE_MAPPING[$typeId]) ? self::TYPE_MAPPING[$typeId] : $typeId;

            // Cas spécial pour RUAMM (deux tranches)
            if ($typeId === 'RUAMM') {
                $plafond = self::PLAFONDS[$typeId];

                if ($base <= $plafond) {
                    // Tranche unique
                    $assiettesPrepared[] = [
                        'type' => $typeXML,
                        'valeur' => round($base * self::TAUX[$typeId]['TRANCHE_1'])
                    ];
                } else {
                    // Deux tranches avec taux différents
                    $assiettesPrepared[] = [
                        'type' => $typeXML,
                        'tranche' => 'TRANCHE_1',
                        'valeur' => round($plafond * self::TAUX[$typeId]['TRANCHE_1'])
                    ];

                    $assiettesPrepared[] = [
                        'type' => $typeXML,
                        'tranche' => 'TRANCHE_2',
                        'valeur' => round(($base - $plafond) * self::TAUX[$typeId]['TRANCHE_2'])
                    ];
                }
            }
            // Pour tous les autres types
            elseif (isset(self::TAUX[$typeId])) {
                // Appliquer plafond si défini
                if (isset(self::PLAFONDS[$typeId])) {
                    $base = min($base, self::PLAFONDS[$typeId]);
                }

                $assiettesPrepared[] = [
                    'type' => $typeXML,
                    'valeur' => round($base * self::TAUX[$typeId])
                ];
            }
        }

        return $assiettesPrepared;
    }
}
