<?php
// index.php (modifié avec avertissement simple)
require_once __DIR__ . '/autoloader.php';

use App\Utils\NommageXml;
use App\Utils\PeriodeManager;
use App\Utils\AssembleurXml;
use App\Utils\FileCounter;
use App\Utils\PeriodesDisponibles;

// Créer une instance de PeriodeManager pour les valeurs par défaut
$periodeDefaut = new PeriodeManager();
$anneeActuelle = $periodeDefaut->getAnnee();
$trimestreActuel = $periodeDefaut->getTrimestre();

// Récupération des valeurs soumises par le formulaire
$anneeSelectionnee = $_POST['annee'] ?? $anneeActuelle;
$trimestreSelectionne = $_POST['trimestre'] ?? $trimestreActuel;

// Vérifier si la période est disponible (pour affichage d'avertissement uniquement)
$periodeDisponible = PeriodesDisponibles::estDisponible((int)$anneeSelectionnee, (int)$trimestreSelectionne);
$avertissement = '';
if (!$periodeDisponible) {
    $avertissement = "Attention : La période sélectionnée (Trimestre $trimestreSelectionne de l'année $anneeSelectionnee) " .
        "n'est pas disponible dans la base de données. Les résultats peuvent être incomplets ou vides.";
}

// Initialisation des variables
$message = '';
$downloadLink = '';
$debugInfo = '';

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generer'])) {
    try {
        // Création et configuration du gestionnaire de période
        $periodeManager = new PeriodeManager();
        $periodeManager->setTrimestre((int)$anneeSelectionnee, (int)$trimestreSelectionne);

        // Afficher les informations de période pour confirmer
        $debugInfo = "Période configurée : du " . $periodeManager->getDateDebut() .
            " au " . $periodeManager->getDateFin() .
            " (Trimestre: " . $periodeManager->getTrimestre() . ", Année: " . $periodeManager->getAnnee() . ")";

        // Générer le XML
        $assembleur = new AssembleurXml($periodeManager);
        $xmlContent = $assembleur->genererDeclarationComplete();

        // Créer le nom de fichier avec séquence automatique
        $fileCounter = new FileCounter();
        $nommageXml = new NommageXml($periodeManager, $fileCounter);
        $nomFichier = $nommageXml->generateName();

        // Sauvegarder le fichier XML
        $dossier = 'declarations';
        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }
        $cheminComplet = $dossier . '/' . $nomFichier;

        if (file_put_contents($cheminComplet, $xmlContent) !== false) {
            $message = "Déclaration générée avec succès : " . $nomFichier;

            // Lien de téléchargement via le script dédié
            $downloadLink = "Télécharger le fichier: <a href='download.php?file=" . urlencode($nomFichier) . "'>" . htmlspecialchars($nomFichier) . "</a>";
        } else {
            throw new \Exception("Erreur lors de la sauvegarde du fichier");
        }
    } catch (\Exception $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Génération Déclaration CAFAT</title>
    <style>
        .avertissement {
            color: red;
            font-weight: bold;
            margin: 15px 0;
        }

        .info-periodes {
            font-style: italic;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <h1>Génération Déclaration Nominative CAFAT</h1>

    <?php if (!empty($message)): ?>
        <p><strong><?php echo $message; ?></strong></p>
    <?php endif; ?>

    <?php if (!empty($debugInfo)): ?>
        <p><em><?php echo $debugInfo; ?></em></p>
    <?php endif; ?>

    <?php if (!empty($downloadLink)): ?>
        <p><?php echo $downloadLink; ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <?php if (!empty($avertissement)): ?>
            <p class="avertissement"><?php echo $avertissement; ?></p>
        <?php endif; ?>

        <div class="info-periodes">
            <p>Périodes disponibles dans la base de données :
                <?php echo PeriodesDisponibles::getPeriodesDisponiblesTexte(); ?></p>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="trimestre">Trimestre :</label>
            <select name="trimestre" id="trimestre">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $trimestreSelectionne) ? 'selected' : ''; ?>>
                        Trimestre <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="annee">Année :</label>
            <input type="number" name="annee" id="annee" value="<?php echo $anneeSelectionnee; ?>" min="2000"
                max="2100">
        </div>

        <p>
            Période par défaut : Trimestre <?php echo $trimestreActuel; ?> de l'année <?php echo $anneeActuelle; ?>
        </p>

        <button type="submit" name="generer">Générer la déclaration</button>
    </form>
</body>

</html>