<?php

namespace App\Utils;

use App\Database\Database;
use App\Utils\PeriodeManager;

/**
 * Classe pour gérer la nomenclature des fichiers XML de déclaration CAFAT trimestrielle
 */
class NommageXml
{
    /**
     * Type de déclaration supporté
     */
    const TYPE_TRIMESTRIEL = 'T';

    /**
     * @var PeriodeManager Instance du gestionnaire de périodes
     */
    private PeriodeManager $periodeManager;

    /**
     * Constructeur
     * 
     * @param PeriodeManager|null $periodeManager Gestionnaire de périodes
     */
    public function __construct(?PeriodeManager $periodeManager = null)
    {
        $this->periodeManager = $periodeManager ?? new PeriodeManager();
    }

    /**
     * Récupère les informations de l'employeur depuis la base de données
     * nécessaires pour le nommage du fichier
     *
     * @return array Tableau contenant numeroEmployeur et suffixeEmployeur
     */
    public static function getEmployeurInfo(): array
    {
        $pdo = Database::getInstance()->getConnection();
        $query = "SELECT 
                    SUBSTRING_INDEX(numerocafat, '/', 1) as numeroEmployeur,
                    SUBSTRING_INDEX(numerocafat, '/', -1) as suffixeEmployeur
                FROM ecfc6.societe 
                LIMIT 1";

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            throw new \RuntimeException("Impossible de récupérer les informations de l'employeur");
        }

        return [
            'numeroEmployeur' => $result['numeroEmployeur'],
            'suffixeEmployeur' => $result['suffixeEmployeur'],
        ];
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
     * Génère le nom de fichier pour la déclaration nominative CAFAT trimestrielle
     * en utilisant la période actuellement configurée dans le PeriodeManager
     *
     * @param int|null $numeroUnique Numéro unique (défaut: 1)
     * @return string Le nom de fichier conforme
     */
    public function generateName(?int $numeroUnique = 1): string
    {
        $annee = $this->periodeManager->getAnnee();
        $trimestre = $this->periodeManager->getTrimestre();

        if ($trimestre < 1 || $trimestre > 4) {
            throw new \InvalidArgumentException("Numéro de trimestre invalide (doit être entre 1 et 4).");
        }

        // Récupération des informations employeur depuis la base de données
        $infoEmployeur = self::getEmployeurInfo();
        $numeroEmployeur = $infoEmployeur['numeroEmployeur'];
        $suffixeEmployeur = $infoEmployeur['suffixeEmployeur'];

        // Formatage des composants
        $anneeFormatee = (string) $annee;
        $trimestreFormate = str_pad($trimestre, 2, '0', STR_PAD_LEFT);
        $numeroEmployeurFormate = str_pad($numeroEmployeur, 7, '0', STR_PAD_LEFT);
        $suffixeEmployeurFormate = str_pad($suffixeEmployeur, 3, '0', STR_PAD_LEFT);
        $numeroUniqueFormate = str_pad($numeroUnique, 3, '0', STR_PAD_LEFT);

        // Construction du nom de fichier selon le format CAFAT
        return sprintf(
            "DN-%s%s%s-%s%s-%s.xml",
            $anneeFormatee,
            self::TYPE_TRIMESTRIEL,
            $trimestreFormate,
            $numeroEmployeurFormate,
            $suffixeEmployeurFormate,
            $numeroUniqueFormate
        );
    }
}
