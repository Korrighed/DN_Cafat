<?php

namespace App\Utils;

use App\Tags\Entete;
use App\Tags\Periode;
use App\Tags\Attributs;
use App\Tags\employeur;
use App\Tags\Assures;
use App\Tags\Decompte;


class XmlAssembler
{
    /**
     * @var string L'encodage du document XML
     */
    private $encoding;

    /**
     * @var array Contient les instances des classes de tags
     */
    private $tagComponents;

    /**
     * @var int L'année de la déclaration
     */
    private $annee;

    /**
     * @var int Le numéro de trimestre (1-4)
     */
    private $trimestre;



    /**
     * Constructeur
     * 
     * @param string $encoding Encodage du document XML
     * @param int $annee Année de la déclaration
     * @param int $trimestre Numéro du trimestre (1-4)
     */
    public function __construct($encoding = "ISO-8859-1", $annee = 2023, $trimestre = 2)
    {
        $this->encoding = $encoding;
        $this->annee = $annee;
        $this->trimestre = $trimestre;

        // Initialisation des composants
        $this->initializeComponents();
    }

    /**
     * Initialise les composants de tags XML
     */
    private function initializeComponents()
    {
        $this->tagComponents = [
            'entete' => new Entete(),
            'periode' => new Periode($this->annee, 'T', $this->trimestre)
        ];

        // D'autres composants pourront être ajoutés ici au fur et à mesure
    }

    /**
     * Définit l'année de la déclaration
     * 
     * @param int $annee
     * @return self
     */
    public function setAnnee($annee)
    {
        $this->annee = $annee;
        $this->updatePeriodeComponent();
        return $this;
    }

    /**
     * Définit le trimestre de la déclaration
     * 
     * @param int $trimestre
     * @return self
     */
    public function setTrimestre($trimestre)
    {
        if ($trimestre < 1 || $trimestre > 4) {
            throw new \InvalidArgumentException("Le trimestre doit être entre 1 et 4");
        }

        $this->trimestre = $trimestre;
        $this->updatePeriodeComponent();
        return $this;
    }

    /**
     * Met à jour le composant de période après changement d'année ou trimestre
     */
    private function updatePeriodeComponent()
    {
        $this->tagComponents['periode'] = new Periode($this->annee, 'T', $this->trimestre);
    }

    /**
     * Ajoute un composant de tag au document
     * 
     * @param string $key La clé du composant
     * @param mixed $component L'instance du composant de tag
     * @return self
     */
    public function addComponent($key, $component)
    {
        $this->tagComponents[$key] = $component;
        return $this;
    }

    /**
     * Génère le document XML complet
     * 
     * @return string Le document XML assemblé
     */
    public function generate()
    {
        $xmlOutput = "<?xml version=\"1.0\" encoding=\"{$this->encoding}\"?>\n";

        //Balise globale
        $xmlOutput .= "<doc>\n";

        // Balise entete
        if (isset($this->tagComponents['entete'])) {
            $xmlOutput .= $this->tagComponents['entete']->generate() . "\n";
        }

        // Ajouter le corps
        $xmlOutput .= " <corps>\n";

        // Balise periode
        if (isset($this->tagComponents['periode'])) {
            $xmlOutput .= $this->tagComponents['periode']->generate() . "\n";
        }

        // Balises attributs
        if (isset($this->tagComponents['attributs'])) {
            $xmlOutput .= $this->tagComponents['attributs']->generate() . "\n";
        }

        // Balise employeur
        if (isset($this->tagComponents['employeur'])) {
            $xmlOutput .= $this->tagComponents['employeur']->generate() . "\n";
        }

        // Balise assures
        if (isset($this->tagComponents['assures'])) {
            $xmlOutput .= $this->tagComponents['assures']->generate() . "\n";
        }

        // Balise decompte
        if (isset($this->tagComponents['decompte'])) {
            $xmlOutput .= $this->tagComponents['decompte']->generate() . "\n";
        }

        $xmlOutput .= " </corps>\n";

        // Fermer le document
        $xmlOutput .= "</doc>";

        return $xmlOutput;
    }

    /**
     * Sauvegarde le XML généré dans un fichier
     *
     * @param string $filePath Chemin où sauvegarder le fichier
     * @return bool True si la sauvegarde a réussi
     */
    public function saveToFile($filePath)
    {
        $xmlContent = $this->generate();
        return file_put_contents($filePath, $xmlContent) !== false;
    }
}
