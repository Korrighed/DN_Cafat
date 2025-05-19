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
     * Plafonds pour chaque type de cotisation
     * @var array
     */
    private const PLAFONDS = [
        'RUAMM_TRANCHE_1' => 548600,
        'CHOMAGE' => 390900,
        'ATMP_PRINCIPAL' => 390900,
        'FSH' => 329700,
        'FIAF' => 548600,
        'RETRAITE' => 390900,
        'CRE' => 468377,
    ];

    /**
     * Taux de cotisation pour chaque type
     * @var array
     */
    private const TAUX = [
        'RUAMM_TRANCHE_1' => 0.1452,
        'RUAMM_TRANCHE_2' => 0.05,
        'FIAF' => 0.002,
        'CHOMAGE' => 0.0206,
        'FDS' => 0.00075,
        'RETRAITE' => 0.1452,
        'ATMP_PRINCIPAL' => 0.0072,
        'FORMATION_PROFESSIONNELLE' => 0.0070,
        'CCS' => 0.03,
        'FSH' => 0.02
    ];

    /**
     * Mapping des types vers le type XML à afficher
     * @var array
     */
    private const TYPE_MAPPING = [
        'COTIS-CAFAT-RETRAITE' => 'RETRAITE',
        'COTIS-CAFAT-CHOMAGE' => 'CHOMAGE',
        'COTIS-CAFAT-PRESTATIONS_FAMILIALES' => 'PRESTATIONS_FAMILIALES'
    ];

    /**
     * Constructeur
     * 
     * @param PeriodeManager|null $periodeManager
     */
    public function __construct(?PeriodeManager $periodeManager = null)
    {
        $this->pdo = Database::getInstance()->getConnection();
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
     * Génère la balise decompte XML
     * 
     * @return string Le XML généré pour la section decompte
     * @throws \RuntimeException Si une erreur survient lors de la génération
     */
    public function generateDecompte(): string
    {
        try {
            $cotisations = $this->getCotisations();
            $totalCotisations = $this->calculerTotalCotisations($cotisations);
            return $this->formaterDecompteXML($cotisations, $totalCotisations);
        } catch (\Exception $e) {
            throw new \RuntimeException("Erreur lors de la génération du décompte: " . $e->getMessage());
        }
    }

    /**
     * Récupère les cotisations depuis la base de données
     * 
     * @return array Liste des cotisations structurées
     */
    private function getCotisations(): array
    {
        $periodeDebut = $this->periodeManager->getPeriodeDebut();
        $periodeFin = $this->periodeManager->getPeriodeFin();

        if (empty($periodeDebut) || empty($periodeFin)) {
            return [];
        }

        $sql = "
        WITH cotisation_types AS (
            SELECT 
                l.id,
                l.bulletin_id,
                l.base,
                l.libelle,
                CASE 
                    WHEN l.libelle LIKE '%RUAMM%' THEN 'RUAMM'
                    WHEN l.libelle LIKE '%FIAF%' THEN 'FIAF'
                    WHEN l.libelle LIKE 'Cotisations CAFA%' THEN 'COTIS-CAFAT'
                    WHEN l.libelle LIKE 'C.R.E.%' THEN 'CRE'
                    WHEN l.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
                    WHEN l.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
                    WHEN l.libelle LIKE '%Accident du travail%' THEN 'ATMP_PRINCIPAL'
                    WHEN l.libelle LIKE '%CS%' THEN 'CCS'
                    WHEN l.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
                    WHEN l.libelle LIKE '%chomage%' THEN 'CHOMAGE'
                END AS type_cotisation
            FROM ligne_bulletin l
            INNER JOIN bulletin b ON l.bulletin_id = b.id
            INNER JOIN rubrique r ON l.rubrique_id = r.id
            WHERE 
                b.periode BETWEEN :debut AND :fin
                AND (
                    l.libelle LIKE '%RUAMM%' OR 
                    l.libelle LIKE 'C.R.E.%' OR 
                    l.libelle LIKE 'Cotisations CAFA%' OR
                    l.libelle LIKE '%FIAF%' OR
                    l.libelle LIKE '%Accident du travail%' OR
                    l.libelle LIKE '%FDS Financement Dialogue Social%' OR
                    l.libelle LIKE '%Formation Professionnelle continue%' OR
                    l.libelle LIKE '%Fond Social de l%Habitat%' OR
                    l.libelle LIKE '%CHOMAGE%' OR
                    l.libelle LIKE '%CS%'
                )
        ),
        cotisation_data AS (
            SELECT 
                type_cotisation,
                SUM(base) AS total_base
            FROM cotisation_types
            GROUP BY type_cotisation
        )
        
        SELECT 
            type_cotisation,
            total_base
        FROM cotisation_data
        WHERE total_base > 0
    ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':debut' => $periodeDebut, ':fin' => $periodeFin]);
            $resultats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Convertir les résultats dans le format attendu par structurerCotisations
            $cotisationsGroupees = [];
            foreach ($resultats as $resultat) {
                $cotisationsGroupees[] = [
                    'type_cotisation' => $resultat['type_cotisation'],
                    'total_base' => (float)$resultat['total_base'],
                    'taux_total' => 0 // Non utilisé dans la nouvelle version mais conservé pour compatibilité
                ];
            }

            return $this->structurerCotisations($cotisationsGroupees);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Erreur lors de la récupération des cotisations: " . $e->getMessage());
        }
    }

    /**
     * Structure les cotisations pour les rendre prêtes pour le XML
     * 
     * @param array $cotisationsRaw Données brutes des cotisations
     * @return array Cotisations structurées
     */
    private function structurerCotisations(array $cotisationsRaw): array
    {
        $cotisations = [];
        $societeInfo = $this->getSocieteInfo();

        foreach ($cotisationsRaw as $cot) {
            $typeCotisation = $cot['type_cotisation'];
            $base = floatval($cot['total_base']);

            // Traitement spécial pour RUAMM (2 tranches)
            if ($typeCotisation === 'RUAMM') {
                $this->ajouterCotisationRuamm($cotisations, $base);
            }
            // Traitement pour AT/MP (utilise le taux de la société)
            else if ($typeCotisation === 'ATMP_PRINCIPAL' && $base > 0) {
                $assiette = min($base, self::PLAFONDS[$typeCotisation] ?? $base);
                $tauxATMP = $societeInfo['tauxat'] / 100; // Conversion en décimal
                $cotisations[] = [
                    'type' => 'ATMP',
                    'tranche' => '',
                    'assiette' => $assiette,
                    'valeur' => $assiette * $tauxATMP
                ];
            }
            // Traitement pour les retraites (mapping spécial)
            else if (isset(self::TYPE_MAPPING[$typeCotisation]) && $base > 0) {
                $assiette = min($base, self::PLAFONDS[$typeCotisation] ?? $base);
                $cotisations[] = [
                    'type' => self::TYPE_MAPPING[$typeCotisation], // Sera 'RETRAITE'
                    'tranche' => '',
                    'assiette' => $assiette,
                    'valeur' => $assiette * self::TAUX[$typeCotisation]
                ];
            }
            // Traitement standard pour les autres
            else if (isset(self::TAUX[$typeCotisation]) && $base > 0) {
                $assiette = min($base, self::PLAFONDS[$typeCotisation] ?? $base);
                $cotisations[] = [
                    'type' => $typeCotisation,
                    'tranche' => '',
                    'assiette' => $assiette,
                    'valeur' => $assiette * self::TAUX[$typeCotisation]
                ];
            }
        }

        return $cotisations;
    }

    /**
     * Ajoute les cotisations RUAMM (avec gestion des tranches)
     * 
     * @param array &$cotisations Tableau des cotisations à compléter
     * @param float $base Base de calcul RUAMM
     */
    private function ajouterCotisationRuamm(array &$cotisations, float $base): void
    {
        if ($base <= 0) {
            return;
        }

        // Tranche 1
        $assietteTranche1 = min($base, self::PLAFONDS['RUAMM_TRANCHE_1']);
        $cotisations[] = [
            'type' => 'RUAMM',
            'tranche' => 'TRANCHE_1',
            'assiette' => $assietteTranche1,
            'valeur' => $assietteTranche1 * self::TAUX['RUAMM_TRANCHE_1']
        ];

        // Tranche 2 si applicable
        if ($base > self::PLAFONDS['RUAMM_TRANCHE_1']) {
            $assietteTranche2 = $base - self::PLAFONDS['RUAMM_TRANCHE_1'];
            $cotisations[] = [
                'type' => 'RUAMM',
                'tranche' => 'TRANCHE_2',
                'assiette' => $assietteTranche2,
                'valeur' => $assietteTranche2 * self::TAUX['RUAMM_TRANCHE_2']
            ];
        }
    }

    /**
     * Récupère les informations de la société, notamment le taux AT/MP
     * 
     * @return array Informations de la société
     */
    private function getSocieteInfo(): array
    {
        $sql = "SELECT * FROM societe LIMIT 1";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['tauxat' => self::TAUX['ATMP_PRINCIPAL'] * 100]; // Valeur par défaut
    }

    /**
     * Calcule le total des cotisations
     * 
     * @param array $cotisations Liste des cotisations
     * @return int Total des cotisations arrondi
     */
    private function calculerTotalCotisations(array $cotisations): int
    {
        $total = 0;
        foreach ($cotisations as $cotisation) {
            $total += $cotisation['valeur'];
        }
        return round($total);
    }

    /**
     * Formate le XML du décompte
     * 
     * @param array $cotisations Liste des cotisations
     * @param int $totalCotisations Total des cotisations
     * @return string XML formaté
     */
    private function formaterDecompteXML(array $cotisations, int $totalCotisations): string
    {
        $xml = "\t\t<decompte>\n";
        $xml .= "\t\t\t<cotisations>\n";

        foreach ($cotisations as $cotisation) {
            $xml .= "\t\t\t\t<cotisation>\n";
            $xml .= "\t\t\t\t\t<type>{$cotisation['type']}</type>\n";

            if (!empty($cotisation['tranche'])) {
                $xml .= "\t\t\t\t\t<tranche>{$cotisation['tranche']}</tranche>\n";
            }

            $xml .= "\t\t\t\t\t<assiette>" . round($cotisation['assiette']) . "</assiette>\n";
            $xml .= "\t\t\t\t\t<valeur>" . round($cotisation['valeur']) . "</valeur>\n";
            $xml .= "\t\t\t\t</cotisation>\n";
        }

        $xml .= "\t\t\t</cotisations>\n";
        $xml .= "\t\t\t<totalCotisations>{$totalCotisations}</totalCotisations>\n";
        $xml .= "\t\t\t<deductions></deductions>\n";
        $xml .= "\t\t\t<montantAPayer>{$totalCotisations}</montantAPayer>\n";
        $xml .= "\t\t</decompte>\n";

        return $xml;
    }
}