<?php
// index.php
require_once __DIR__ . '/autoloader.php';

use App\Utils\NommageXml;
use App\Utils\PeriodeManager;
use App\Utils\AssembleurXml;

// Récupération des valeurs actuelles (par défaut)
$anneeActuelle = date('Y');
$trimestreActuel = ceil(date('n') / 3); // Détermine le trimestre actuel (1-4)

// Récupération des valeurs soumises par le formulaire
$anneeSelectionnee = $_POST['annee'] ?? $anneeActuelle;
$trimestreSelectionne = $_POST['trimestre'] ?? $trimestreActuel;

// Initialisation des variables
$message = '';
$downloadLink = '';
$debugInfo = '';

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generer'])) {
    try {
        // Créer un gestionnaire de période pour le trimestre sélectionné
        $periodeManager = new PeriodeManager();
        $periodeManager->setTrimestre((int)$anneeSelectionnee, (int)$trimestreSelectionne);

        // Afficher les informations de période pour confirmer
        $debugInfo = "Période configurée : du " . $periodeManager->getDateDebut() .
            " au " . $periodeManager->getDateFin() .
            " (Trimestre: " . $periodeManager->getTrimestre() . ", Année: " . $periodeManager->getAnnee() . ")";

        // Générer le XML
        $assembleur = new AssembleurXml($periodeManager);
        $xmlContent = $assembleur->genererDeclarationComplete();

        // Créer le nom de fichier
        $nommageXml = new NommageXml($periodeManager);
        $nomFichier = $nommageXml->generateName(1); // 1 est le numéro de séquence

        // Sauvegarder le fichier XML
        $dossier = 'declarations';
        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }
        $cheminComplet = $dossier . '/' . $nomFichier;

        if (file_put_contents($cheminComplet, $xmlContent) !== false) {
            $message = "Déclaration générée avec succès : " . $nomFichier;

            // Proposer le téléchargement du fichier
            $downloadLink = "Télécharger le fichier: <a href='$cheminComplet' download>$nomFichier</a>";
        } else {
            $message = "Erreur lors de la sauvegarde de la déclaration.";
        }
    } catch (\Exception $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Génération Déclaration CAFAT</title>
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