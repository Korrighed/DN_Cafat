<?php

namespace App\Tags;

/**
 * Gère la génération de la section <periode> du XML CAFAT
 */
class Periode
{
    private string $type = 'TRIMESTRIEL';
    private int $annee;
    private int $numero;

    /**
     * Constructeur avec validation des paramètres
     * 
     * @param int $annee Année entre 2000 et 3000
     * @param int $numero Numéro de trimestre entre 1 et 4
     * @throws \InvalidArgumentException Si les paramètres ne sont pas valides
     */
    public function __construct(int $annee = 2023, int $numero = 2)
    {
        $this->setAnnee($annee);
        $this->setNumero($numero);
    }

    /**
     * Définit l'année de la période avec validation
     * 
     * @param int $annee Année entre 2000 et 3000
     * @throws \InvalidArgumentException Si l'année n'est pas valide
     */
    public function setAnnee(int $annee): self
    {
        if ($annee < 2000 || $annee > 3000) {
            throw new \InvalidArgumentException('L\'année doit être comprise entre 2000 et 3000');
        }

        $this->annee = $annee;
        return $this;
    }

    /**
     * Définit le numéro de trimestre avec validation
     * 
     * @param int $numero Numéro de trimestre entre 1 et 4
     * @throws \InvalidArgumentException Si le numéro n'est pas valide
     */
    public function setNumero(int $numero): self
    {
        if ($numero < 1 || $numero > 4) {
            throw new \InvalidArgumentException('Le numéro du trimestre doit être compris entre 1 et 4');
        }

        $this->numero = $numero;
        return $this;
    }


    public function getAnnee(): int
    {
        return $this->annee;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function generate(): string
    {

        $xml = <<<XML
  <periode>
    <type>{$this->type}</type>
    <annee>{$this->annee}</annee>
    <numero>{$this->numero}</numero>
  </periode>
XML;
        return $xml;
    }
}
