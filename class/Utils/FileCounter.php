<?php

namespace App\Utils;

/**
 * Classe pour gérer le comptage des fichiers générés par période
 */
class FileCounter
{
    /**
     * Chemin vers le fichier de comptage
     */
    private string $counterFile;

    /**
     * Constructeur
     * 
     * @param string|null $counterFile Chemin vers le fichier de comptage
     */
    public function __construct(?string $counterFile = null)
    {
        $this->counterFile = $counterFile ?? __DIR__ . '/../../data/file_counters.json';

        // S'assurer que le répertoire existe
        $dir = dirname($this->counterFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Créer le fichier s'il n'existe pas
        if (!file_exists($this->counterFile)) {
            file_put_contents($this->counterFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Obtient le prochain numéro de séquence pour une période donnée
     * 
     * @param int $annee Année de la période
     * @param int $trimestre Trimestre de la période
     * @return int Prochain numéro de séquence
     */
    public function getNextSequenceNumber(int $annee, int $trimestre): int
    {
        $key = $this->buildKey($annee, $trimestre);
        $counters = $this->loadCounters();

        // Si la clé n'existe pas, commencer à 1
        if (!isset($counters[$key])) {
            $counters[$key] = 0;
        }

        // Incrémenter le compteur
        $counters[$key]++;

        // Sauvegarder les compteurs
        $this->saveCounters($counters);

        return $counters[$key];
    }

    /**
     * Construit une clé unique pour une période
     * 
     * @param int $annee Année de la période
     * @param int $trimestre Trimestre de la période
     * @return string Clé unique
     */
    private function buildKey(int $annee, int $trimestre): string
    {
        return sprintf("%d-T%d", $annee, $trimestre);
    }

    /**
     * Charge les compteurs depuis le fichier
     * 
     * @return array Tableau des compteurs
     */
    private function loadCounters(): array
    {
        $content = file_get_contents($this->counterFile);
        return json_decode($content, true) ?: [];
    }

    /**
     * Sauvegarde les compteurs dans le fichier
     * 
     * @param array $counters Tableau des compteurs
     * @return void
     */
    private function saveCounters(array $counters): void
    {
        file_put_contents($this->counterFile, json_encode($counters, JSON_PRETTY_PRINT));
    }
}
