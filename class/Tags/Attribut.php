<?php

namespace App\Tags;

use App\Database\Database;
use App\Utils\PeriodeManager;
use PDO;

/**
 * Classe gérant la génération de la balise <attributs> pour la déclaration CAFAT
 */
class Attribut
{
    /** @var PeriodeManager Gestionnaire de période */
    private PeriodeManager $periodeManager;

    /** @var bool Si c'est une DN complémentaire */
    private bool $complementaire = false;

    /**
     * Constructeur
     * 
     * @param PeriodeManager|null $periodeManager Gestionnaire de période (optionnel)
     */
    public function __construct(?PeriodeManager $periodeManager = null)
    {
        $this->periodeManager = $periodeManager ?? new PeriodeManager();
    }

    /**
     * Définit le gestionnaire de période
     *
     * @param PeriodeManager $periodeManager Gestionnaire de période
     * @return self Pour chaînage de méthodes
     */
    public function setPeriodeManager(PeriodeManager $periodeManager): self
    {
        $this->periodeManager = $periodeManager;
        return $this;
    }

    /**
     * Définit si c'est une DN complémentaire
     * 
     * @param bool $value Valeur à définir
     * @return self Pour chaînage de méthodes
     */
    public function setComplementaire(bool $value): self
    {
        $this->complementaire = $value;
        return $this;
    }

    /**
     * Génère la liste des périodes (au format YYYYMM) pour le trimestre configuré
     * 
     * @return array Tableau des périodes au format YYYYMM
     */
    private function getPeriodes(): array
    {
        $dateDebut = new \DateTime($this->periodeManager->getDateDebut());
        $dateFin = new \DateTime($this->periodeManager->getDateFin());
        $interval = new \DateInterval('P1M');
        $periodes = [];

        $dateCourante = clone $dateDebut;
        while ($dateCourante <= $dateFin) {
            $periodes[] = $dateCourante->format('Ym');
            $dateCourante->add($interval);
        }

        return $periodes;
    }

    /**
     * Vérifie s'il y a des bulletins pour la période
     * 
     * @return bool True si aucun bulletin n'existe pour la période
     */
    private function pasAssureRemunere(): bool
    {
        $periodes = $this->getPeriodes();
        $periodesStr = implode("', '", $periodes);

        $pdo = Database::getInstance()->getConnection();

        $query = "
            SELECT COUNT(*) as nbBulletins
            FROM bulletin
            WHERE periode IN ('$periodesStr')
        ";

        $stmt = $pdo->query($query);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['nbBulletins'] == 0;
    }

    /**
     * Génère le XML pour la balise <attributs>
     * 
     * @return string XML pour la balise attributs
     */
    public function generateAttribut(): string
    {
        // Déterminer si l'employeur n'a pas occupé de personnel pendant la période
        $pasAssureRemunere = $this->pasAssureRemunere() ? 'true' : 'false';

        // Valeur configurée pour complementaire, autres valeurs fixes
        $complementaire = $this->complementaire ? 'true' : 'false';

        return <<<XML
        <attributs>()
            <complementaire>$complementaire</complementaire>
            <contratAlternance>false</contratAlternance>
            <pasAssureRemunere>$pasAssureRemunere</pasAssureRemunere>
            <pasDeReembauche>false</pasDeReembauche>
        </attributs>
        XML;
    }
}
