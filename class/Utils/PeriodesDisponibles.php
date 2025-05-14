<?php
// class/Utils/PeriodesDisponibles.php
namespace App\Utils;

class PeriodesDisponibles
{
    // Tableau des périodes disponibles dans la base de données
    private static array $periodesDisponibles = [
        2022 => [1, 2, 3, 4],  // Tous les trimestres de 2022 sont disponibles
        2023 => [1, 2]          // Seulement T1 et T2 (partiel) pour 2023
    ];

    /**
     * Vérifie si une période (année/trimestre) est disponible
     *
     * @param int $annee
     * @param int $trimestre
     * @return bool
     */
    public static function estDisponible(int $annee, int $trimestre): bool
    {
        return isset(self::$periodesDisponibles[$annee]) &&
            in_array($trimestre, self::$periodesDisponibles[$annee]);
    }

    /**
     * Retourne toutes les périodes disponibles pour l'affichage
     *
     * @return string
     */
    public static function getPeriodesDisponiblesTexte(): string
    {
        $texte = [];

        foreach (self::$periodesDisponibles as $annee => $trimestres) {
            $texte[] = "$annee: Trimestres " . implode(', ', $trimestres);
        }

        return implode(' | ', $texte);
    }
}
