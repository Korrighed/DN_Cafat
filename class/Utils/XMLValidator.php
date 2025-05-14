<?php

namespace App\Utils;

use DOMDocument;
use LibXMLError;

/**
 * Classe pour la validation des documents XML contre un schéma XSD
 */
class XMLValidator
{
    /**
     * @var string Chemin vers le fichier XSD
     */
    private string $xsdPath;

    /**
     * Constructeur
     * 
     * @param string|null $xsdPath Chemin vers le fichier XSD (optionnel)
     */
    public function __construct(?string $xsdPath = null)
    {
        $this->xsdPath = $xsdPath ?? dirname(__DIR__, 2) . '/test/app-validator-2.0.xsd';
    }

    /**
     * Définit le chemin vers le fichier XSD
     * 
     * @param string $xsdPath Chemin vers le fichier XSD
     * @return self Pour chaînage de méthodes
     */
    public function setXsdPath(string $xsdPath): self
    {
        $this->xsdPath = $xsdPath;
        return $this;
    }
    /**
     * Valide un document XML contre le schéma XSD
     * 
     * @param string $xmlContent Contenu XML à valider
     * @return array Résultat de la validation [success, messages]
     */
    public function validateXmlContent(string $xmlContent): array
    {
        // Sauvegarde les paramètres d'erreur de libxml
        $useInternalErrors = libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadXML($xmlContent);

        $isValid = $dom->schemaValidate($this->xsdPath);

        $errors = libxml_get_errors();
        $messages = $this->formatLibXmlErrors($errors);
        libxml_clear_errors();

        // Restaure les paramètres d'erreur de libxml
        libxml_use_internal_errors($useInternalErrors);

        return [
            'success' => $isValid,
            'messages' => $messages
        ];
    }

    /**
     * Valide un fichier XML contre le schéma XSD
     * 
     * @param string $xmlPath Chemin vers le fichier XML à valider
     * @return array Résultat de la validation [success, messages]
     */
    public function validateXmlFile(string $xmlPath): array
    {
        if (!file_exists($xmlPath)) {
            return [
                'success' => false,
                'messages' => ['Le fichier XML spécifié n\'existe pas : ' . $xmlPath]
            ];
        }

        $xmlContent = file_get_contents($xmlPath);
        return $this->validateXmlContent($xmlContent);
    }

    /**
     * Formate les erreurs LibXML en messages lisibles
     * 
     * @param array $errors Tableau d'erreurs LibXML
     * @return array Tableau de messages d'erreur formatés
     */
    private function formatLibXmlErrors(array $errors): array
    {
        $messages = [];

        foreach ($errors as $error) {
            $messages[] = $this->formatLibXmlError($error);
        }

        return $messages;
    }

    /**
     * Formate une erreur LibXML en message lisible
     * 
     * @param LibXMLError $error Erreur LibXML
     * @return string Message d'erreur formaté
     */
    private function formatLibXmlError(LibXMLError $error): string
    {
        $errorTypes = [
            LIBXML_ERR_WARNING => 'Avertissement',
            LIBXML_ERR_ERROR => 'Erreur',
            LIBXML_ERR_FATAL => 'Erreur fatale'
        ];

        $type = $errorTypes[$error->level] ?? 'Inconnu';

        return sprintf(
            '%s (ligne %d, colonne %d) : %s',
            $type,
            $error->line,
            $error->column,
            trim($error->message)
        );
    }
}
