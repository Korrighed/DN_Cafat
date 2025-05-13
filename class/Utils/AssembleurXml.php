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
        // Générer chaque partie du document
        $entete = (new Entete())->generateEnTete();
        $periode = (new Periode())->generatePeriode();
        $attribut = (new Attribut())->generateAttribut();
        $employeur = (new Employeur())->genererEmployeur();

        // Pour les assurés, nous avons un tableau indexé par numcafat, nous devons les concaténer
        $assure = new Assure();
        $assuresArray = $assure->genererAssure();
        $assuresXML = '';
        foreach ($assuresArray as $xml) {
            $assuresXML .= $xml;
        }

        // Générer le décompte
        $decompte = new Decompte();
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
