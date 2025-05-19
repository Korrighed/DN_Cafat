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
     * Plafonds pour chaque type de cotisation
     * @var array
     */
    private const PLAFONDS = [
        'RUAMM' => 548600,
        'FIAF' => 548600,
        'RETRAITE' => 548600,
        'CHOMAGE' => 390900,
        'PRESTATIONS_FAMILIALES' => 390900,
        'ATMP' => 390900,
        'FSH' => 329700,
        'CRE' => 468377,
        'FORMATION_PROFESSIONNELLE' => PHP_INT_MAX  // Pas de plafond mentionné dans le SQL
    ];

    /**
     * Taux de cotisation pour chaque type
     * @var array
     */
    private const TAUX = [
        'RUAMM' => [
            'TRANCHE_1' => 0.0285,
            'TRANCHE_2' => 0.0125
        ],
        'FIAF' => 0.0,
        'RETRAITE' => 0.0420,
        'CHOMAGE' => 0.0034,
        'PRESTATIONS_FAMILIALES' => 0.0,
        'ATMP' => 0.0,
        'FSH' => 0.0,
        'FORMATION_PROFESSIONNELLE' => 0.0,
        'CRE' => 0.0315
    ];

    /**
     * Mapping des identifiants SQL vers les types XML
     * @var array
     */
    private const TYPE_MAPPING = [
        'COTIS-CAFAT-RETRAITE' => 'RETRAITE',
        'COTIS-CAFAT-CHOMAGE' => 'CHOMAGE',
        'COTIS-CAFAT-PRESTATIONS_FAMILIALES' => 'PRESTATIONS_FAMILIALES'
    ];

    /**
     * Gestionnaire de périodes
     * @var PeriodeManager
     */
    private $periodeManager;

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

            // Construction des conditions pour la période
            $whereClause = "salarie_id = :salarie_id";
            $params = [':salarie_id' => $salarieId];

            if ($periode) {
                $periodeClause = "periode = :periode";
                $params[':periode'] = $periode;
            } else {
                $periodeClause = "periode BETWEEN :debut AND :fin";
                $params[':debut'] = $this->periodeManager->getPeriodeDebut();
                $params[':fin'] = $this->periodeManager->getPeriodeFin();
            }

            // Récupération des assiettes depuis la base de données
            $sql = "
                SELECT 
                    l.id,
                    l.bulletin_id,
                    b.salarie_id,
                    l.base,  
                    l.libelle,
                    CASE 
                        WHEN l.libelle LIKE '%RUAMM%' THEN 'RUAMM'
                        WHEN l.libelle LIKE '%FIAF%' THEN 'FIAF'
                        WHEN l.libelle LIKE 'Cotisations CAFA%' THEN 'COTIS-CAFAT'
                        WHEN l.libelle LIKE '%Accident du travail%' THEN 'ATMP'
                        WHEN l.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
                        WHEN l.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
                        WHEN l.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
                        WHEN l.libelle LIKE 'C.R.E.%' THEN 'CRE'
                    END AS type_identifier
                FROM ligne_bulletin l
                INNER JOIN bulletin b ON l.bulletin_id = b.id
                INNER JOIN rubrique r ON l.rubrique_id = r.id
                WHERE 
                    $periodeClause
                    AND $whereClause
                    AND (
                        l.libelle LIKE '%RUAMM%' OR 
                        l.libelle LIKE '%FIAF%' OR
                        l.libelle LIKE 'Cotisations CAFA%' OR
                        l.libelle LIKE '%Accident du travail%' OR
                        l.libelle LIKE '%FDS Financement Dialogue Social%' OR
                        l.libelle LIKE '%Formation Professionnelle continue%' OR
                        l.libelle LIKE '%Fond Social de l%Habitat%' OR
                        l.libelle LIKE 'C.R.E.%'
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

        // Traiter chaque type d'assiette regroupé
        foreach ($assiettesByType as $typeId => $base) {
            $base = (float)$base;

            // Cas spécial RUAMM (deux tranches possibles)
            if ($typeId === 'RUAMM') {
                $this->ajouterAssietteRuamm($assiettesPrepared, $base);
            }
            // Cas spécial COTIS-CAFAT (génère 3 types d'assiettes)
            elseif ($typeId === 'COTIS-CAFAT') {
                $this->ajouterAssietteCotisCafat($assiettesPrepared, $base);
            }
            // Cas standard pour les autres types
            else {
                $this->ajouterAssietteStandard($assiettesPrepared, $typeId, $base);
            }
        }

        return $assiettesPrepared;
    }

    /**
     * Ajoute une assiette RUAMM (avec gestion des tranches)
     * 
     * @param array &$assiettes Tableau des assiettes à compléter
     * @param float $base Base de calcul RUAMM
     */
    private function ajouterAssietteRuamm(array &$assiettes, float $base): void
    {
        $plafond = self::PLAFONDS['RUAMM'];

        if ($base <= $plafond) {
            // Tranche unique
            $assiettes[] = [
                'type' => 'RUAMM',
                'valeur' => round($base * self::TAUX['RUAMM']['TRANCHE_1'])
            ];
        } else {
            // Deux tranches avec taux différents
            $assiettes[] = [
                'type' => 'RUAMM',
                'tranche' => 'TRANCHE_1',
                'valeur' => round($plafond * self::TAUX['RUAMM']['TRANCHE_1'])
            ];

            $assiettes[] = [
                'type' => 'RUAMM',
                'tranche' => 'TRANCHE_2',
                'valeur' => round(($base - $plafond) * self::TAUX['RUAMM']['TRANCHE_2'])
            ];
        }
    }

    /**
     * Ajoute les assiettes pour COTIS-CAFAT (génère 3 types)
     * 
     * @param array &$assiettes Tableau des assiettes à compléter
     * @param float $base Base de calcul
     */
    private function ajouterAssietteCotisCafat(array &$assiettes, float $base): void
    {
        // RETRAITE (plafond à 548600)
        $plafondRetraite = self::PLAFONDS['RETRAITE'];
        $assietteRetraite = min($base, $plafondRetraite);
        $assiettes[] = [
            'type' => 'RETRAITE',
            'valeur' => round($assietteRetraite * self::TAUX['RETRAITE'])
        ];

        // CHOMAGE (plafond à 390900)
        $plafondChomage = self::PLAFONDS['CHOMAGE'];
        $assietteChomage = min($base, $plafondChomage);
        $assiettes[] = [
            'type' => 'CHOMAGE',
            'valeur' => round($assietteChomage * self::TAUX['CHOMAGE'])
        ];

        // PRESTATIONS_FAMILIALES (plafond à 390900)
        $plafondPF = self::PLAFONDS['PRESTATIONS_FAMILIALES'];
        $assiettePF = min($base, $plafondPF);
        $assiettes[] = [
            'type' => 'PRESTATIONS_FAMILIALES',
            'valeur' => round($assiettePF * self::TAUX['PRESTATIONS_FAMILIALES'])
        ];
    }

    /**
     * Ajoute une assiette standard
     * 
     * @param array &$assiettes Tableau des assiettes à compléter
     * @param string $typeId Type d'assiette
     * @param float $base Base de calcul
     */
    private function ajouterAssietteStandard(array &$assiettes, string $typeId, float $base): void
    {
        // Vérifier si ce type est dans notre mapping
        $typeXML = isset(self::TYPE_MAPPING[$typeId]) ? self::TYPE_MAPPING[$typeId] : $typeId;

        // Vérifier si nous avons un taux pour ce type
        if (!isset(self::TAUX[$typeXML])) {
            return; // Sortir si le type n'a pas de taux défini
        }

        // Appliquer le plafond si défini pour ce type
        $plafond = self::PLAFONDS[$typeXML] ?? null;
        $assiette = $plafond ? min($base, $plafond) : $base;

        $taux = self::TAUX[$typeXML];

        // Si c'est un tableau (comme RUAMM), prendre le premier taux
        $taux = is_array($taux) ? $taux['TRANCHE_1'] : $taux;

        $assiettes[] = [
            'type' => $typeXML,
            'valeur' => round($assiette * $taux)
        ];
    }
}
