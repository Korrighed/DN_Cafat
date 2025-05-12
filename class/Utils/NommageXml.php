<?php

namespace App\Utils;

use App\Database\Database;

/**
 * Classe pour gérer la nomenclature des fichiers XML de déclaration CAFAT
 */
class NommageXml
{
    /**
     * Récupère les informations de l'employeur depuis la base de données
     * nécessaires pour le nommage du fichier
     *
     * @param PDO $pdo Connexion à la base de données
     * @return array Tableau contenant numeroEmployeur et suffixeEmployeur
     */
    public static function getEmployeurInfo()
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
     * Génère le nom de fichier pour la déclaration nominative CAFAT 
     *
     * @param PDO $pdo Connexion à la base de données
     * @param int $annee Année de la période déclarée (ex: 2023)
     * @param string $typePeriode Type de période (T pour Trimestrielle)
     * @param int $numeroPeriode Numéro de la période (1-4 pour trimestre)
     * @param int $numeroUnique Numéro unique (défaut: 1)
     * @return string Le nom de fichier conforme
     */
    public static function genererateName($annee = 2023, $typePeriode = 'T', $numeroPeriode = 2, $numeroUnique = 1)
    {

        if ($numeroPeriode < 1 || $numeroPeriode > 4) {
            throw new \InvalidArgumentException("Numéro de période invalide pour une déclaration trimestrielle (doit être entre 1 et 4).");
        }

        // Récupération des informations employeur depuis la base de données
        $infoEmployeur = self::getEmployeurInfo();
        $numeroEmployeur = $infoEmployeur['numeroEmployeur'];
        $suffixeEmployeur = $infoEmployeur['suffixeEmployeur'];

        // Formatage des composants
        $anneeFormatee = (string) $annee;
        $numeroPeriodeFormate = str_pad($numeroPeriode, 2, '0', STR_PAD_LEFT);
        $numeroEmployeurFormate = str_pad($numeroEmployeur, 7, '0', STR_PAD_LEFT);
        $suffixeEmployeurFormate = str_pad($suffixeEmployeur, 3, '0', STR_PAD_LEFT);
        $numeroUniqueFormate = str_pad($numeroUnique, 3, '0', STR_PAD_LEFT);

        // Construction du nom de fichier
        $nomFichier = sprintf(
            "DN-%s%s%s-%s%s-%s.xml",
            $anneeFormatee,
            $typePeriode,
            $numeroPeriodeFormate,
            $numeroEmployeurFormate,
            $suffixeEmployeurFormate,
            $numeroUniqueFormate
        );

        return $nomFichier;
    }

    /**
     * Valide si un nom de fichier correspond aux spécifications CAFAT
     * 
     * @param string $nomFichier Le nom du fichier à valider
     * @return bool True si le nom est valide, false sinon
     */
    public static function ValidName($nomFichier)
    {
        $pattern = '/^DN-\d{4}[T]\d{2}-\d{7}\d{3}-\d{3}\.xml$/';
        return (bool) preg_match($pattern, $nomFichier);
    }
}
