<?php

namespace App\Utils;

use App\Tags\Entete;
use App\Tags\Periode;
use App\Tags\Attribut;
use App\Tags\Employeur;
use App\Tags\Assure;
use App\Tags\Decompte;
use App\Utils\PeriodeManager;

class AssembleurXML
{
    private $periodeManager;

    /**
     * Constructeur avec un gestionnaire de période optionnel
     */
    public function __construct(?PeriodeManager $periodeManager = null)
    {
        // Si aucun gestionnaire de période n'est fourni, on en crée un nouveau avec les valeurs par défaut
        $this->periodeManager = $periodeManager ?? new PeriodeManager();
    }

    /**
     * Permet de modifier la période après l'instanciation
     */
    public function setPeriodeManager(PeriodeManager $periodeManager): self
    {
        $this->periodeManager = $periodeManager;
        return $this;
    }

    /**
     * Génère le document XML complet de la déclaration CAFAT
     * 
     * @return string Le document XML complet
     */
    public function genererDeclarationComplete(): string
    {

        // Génération des parties qui n'utilisent pas PeriodeManager
        $entete = (new Entete())->generateEnTete();
        $employeur = (new Employeur())->genererEmployeur();

        // Génération des parties qui utilisent PeriodeManager
        $periode = (new Periode($this->periodeManager))->generatePeriode();
        $attribut = (new Attribut($this->periodeManager))->generateAttribut();

        // Pour les assurés
        $assure = new Assure($this->periodeManager);
        $assuresArray = $assure->genererAssure();
        $assuresXML = '';
        foreach ($assuresArray as $xml) {
            $assuresXML .= $xml;
        }

        // Pour Decompte
        $decompte = new Decompte($this->periodeManager);
        $decompteXML = $decompte->generateDecompte();

        // Assembler le document complet
        $xmlDocument = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
        $xmlDocument .= "<doc>\n";
        $xmlDocument .= $entete;
        $xmlDocument .= "\t<corps>\n";
        $xmlDocument .= $periode;
        $xmlDocument .= $attribut;
        $xmlDocument .= $employeur;
        $xmlDocument .= "\t\t<assures>\n\n";
        $xmlDocument .= $assuresXML;
        $xmlDocument .= "\t\t</assures>\n\n";
        $xmlDocument .= $decompteXML;
        $xmlDocument .= "\t</corps>\n";
        $xmlDocument .= "</doc>";

        return $xmlDocument;
    }
}
