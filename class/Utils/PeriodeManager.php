<?php

namespace App\Utils;

/**
 * Classe utilitaire pour gérer les périodes (dates et trimestres)
 */
class PeriodeManager
{
    /**
     * @var string Date de début de période (format Y-m-d)
     */
    private $dateDebut;

    /**
     * @var string Date de fin de période (format Y-m-d)
     */
    private $dateFin;

    /**
     * Constructeur
     * 
     * @param string $dateDebut Date de début (format Y-m-d)
     * @param string $dateFin Date de fin (format Y-m-d)
     */
    public function __construct(string $dateDebut = '2022-04-01', string $dateFin = '2022-06-30')
    {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
    }

    /**
     * Définit la période avec des dates spécifiques
     *
     * @param string $dateDebut Date de début (format Y-m-d)
     * @param string $dateFin Date de fin (format Y-m-d)
     * @return self Pour chaînage de méthodes
     */
    public function setPeriode(string $dateDebut, string $dateFin): self
    {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        return $this;
    }

    /**
     * Définit la période sur la base d'une année et d'un trimestre
     *
     * @param int $annee Année (ex: 2023)
     * @param int $trimestre Trimestre (1-4)
     * @return self Pour chaînage de méthodes
     * @throws \InvalidArgumentException Si le trimestre n'est pas valide
     */
    public function setTrimestre(int $annee, int $trimestre): self
    {
        if ($trimestre < 1 || $trimestre > 4) {
            throw new \InvalidArgumentException("Le trimestre doit être compris entre 1 et 4");
        }

        switch ($trimestre) {
            case 1:
                $this->dateDebut = "$annee-01-01";
                $this->dateFin = "$annee-03-31";
                break;
            case 2:
                $this->dateDebut = "$annee-04-01";
                $this->dateFin = "$annee-06-30";
                break;
            case 3:
                $this->dateDebut = "$annee-07-01";
                $this->dateFin = "$annee-09-30";
                break;
            case 4:
                $this->dateDebut = "$annee-10-01";
                $this->dateFin = "$annee-12-31";
                break;
        }

        return $this;
    }

    /**
     * Récupère la date de début
     * 
     * @return string Date de début (format Y-m-d)
     */
    public function getDateDebut(): string
    {
        return $this->dateDebut;
    }

    /**
     * Récupère la date de fin
     * 
     * @return string Date de fin (format Y-m-d)
     */
    public function getDateFin(): string
    {
        return $this->dateFin;
    }

    /**
     * Récupère la période de début au format YYYYMM
     * 
     * @return string Période de début (format YYYYMM)
     */
    public function getPeriodeDebut(): string
    {
        return date('Ym', strtotime($this->dateDebut));
    }

    /**
     * Récupère la période de fin au format YYYYMM
     * 
     * @return string Période de fin (format YYYYMM)
     */
    public function getPeriodeFin(): string
    {
        return date('Ym', strtotime($this->dateFin));
    }
}
