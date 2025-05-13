<?php

namespace App\Tags;

use App\Utils\PeriodeManager;

/**
 * Gère la génération de la section <periode> du XML CAFAT
 */
class Periode
{
    private string $type = 'TRIMESTRIEL';
    private PeriodeManager $periodeManager;

    /**
     * @param PeriodeManager|null $periodeManager Gestionnaire de période
     */
    public function __construct(?PeriodeManager $periodeManager = null)
    {
        $this->periodeManager = $periodeManager ?? new PeriodeManager();
    }

    /**
     * @param PeriodeManager $periodeManager Gestionnaire de période
     * @return self Pour chaînage de méthodes
     */
    public function setPeriodeManager(PeriodeManager $periodeManager): self
    {
        $this->periodeManager = $periodeManager;
        return $this;
    }

    /**
     * @return int Année
     */
    public function getAnnee(): int
    {
        return $this->periodeManager->getAnnee();
    }

    /**
     * @return int Numéro de trimestre
     */
    public function getNumero(): int
    {
        return $this->periodeManager->getTrimestre();
    }

    /**
     * @return string Balise XML
     */
    public function generatePeriode(): string
    {
        $annee = $this->getAnnee();
        $numero = $this->getNumero();

        $xml = "\t\t<periode>\n";
        $xml .= "\t\t\t<type>{$this->type}</type>\n";
        $xml .= "\t\t\t<annee>{$annee}</annee>\n";
        $xml .= "\t\t\t<numero>{$numero}</numero>\n";
        $xml .= "\t\t</periode>\n\n";

        return $xml;
    }
}
