<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 0.3 Avril 2025
 *
 * Script via API data.economie.gouv.fr
 * 
 */

require "../MiniPaviCli.php";
require "../DisplayPaginatedText.php";
require "MiniCarbu.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR|E_WARNING);
ini_set('display_errors',1);

try {
    MiniPavi\MiniPaviCli::start();

    // Initialisation du contexte utilisateur
    if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
        $context = array('step' => 'accueil');
    } else {
        $context = unserialize(MiniPavi\MiniPaviCli::$context);
    }

    // Initialisation des variables
    $vdt = ''; // Le contenu vidéotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPavi
    $directCall = false; // Ne pas rappeler le script immédiatement

    // Gestion de la navigation utilisateur
    switch ($context['step']) {
        case 'accueil':
            // Affichage de la demande de question
            $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
            $vdt .= file_get_contents('Carbu.vdt');
            $vdt .= MiniPavi\MiniPaviCli::setPos(1, 12);
            $vdt .= VDT_TXTWHITE . "Ville ou code postal :";
            $cmd = MiniPavi\MiniPaviCli::createInputTxtCmd(1, 13, 40, MSK_ENVOI, true, '.', '');
            $context['step'] = 'attente_reponse';
            break;

        case 'attente_reponse':
            // Récupération de la question de l'utilisateur
            $location = MiniPavi\MiniPaviCli::$content[0]; // Exemple de ville ou code postal fourni par l'utilisateur
            list($latitude, $longitude) = getCoordinatesFromOpenMeteo($location);
            $nearbyStations = getNearbyStations($latitude, $longitude);
            $textFilename = 'stations.txt'; // Nom du fichier où les informations seront écrites
            displayFuelPrices($nearbyStations, $textFilename);

            // Affichage de la réponse
            $objDisplayPaginatedText = @$context['objDisplayPaginatedText'];
            if (! ($objDisplayPaginatedText instanceof DisplayPaginatedText)) {

                // L'utilisateur n'a pas l'objet dans son contexte : il vient d'arriver sur cette rubrique
                $vdtStart = MiniPavi\MiniPaviCli::clearScreen();
                // Effacement du texte affiché
                $vdtClearPage = MiniPavi\MiniPaviCli::setPos(3, 23);
                $vdtClearPage .= VDT_TXTWHITE . VDT_FDNORM . MiniPavi\MiniPaviCli::repeatChar(' ', 33);
                for ($i = 0; $i < 18; $i++) {
                    $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(1, 21 - $i);
                    $vdtClearPage .= VDT_BGBLUE . MiniPavi\MiniPaviCli::repeatChar(' ', 33);
                }
                // titre Cyan, double hauteur
                $vdtPreTitle = '';

                // Position du titre
                $lTitle = 2;
                $cTitle = 2;
                // Position du compteur de page
                $lCounter = 21;
                $cCounter = 35;

                // Compteur de page couleur Cyan
                $vdtPreCounter = VDT_TXTCYAN;

                // Position début du texte
                $lText = 3;
                $cText = 2;

                // Longueur maximum d'une ligne
                $maxLengthText = 38;

                // Couleur normale : jaune
                $normalColor = VDT_TXTWHITE;

                // Couleur spéciale : blanc
                $specialColor = VDT_TXTYELLOW;

                // Rien de particulier à afficher avant chaque ligne
                $vdtPreText = '';

                // Bas de page si ni Suite ni Retour acceptés (Sommaire n'est pas géré par l'objet, mais directement par le script)
                $vdtNone = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTWHITE . VDT_FDINV . " Sommaire ";

                // Bas de page si uniquement Suite accepté
                $vdtSuite = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTWHITE . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                // Bas de page si uniquement Retour accepté
                $vdtRetour = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTWHITE . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                // Bas de page si Suite et Retour acceptés
                $vdtSuiteRetour = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTWHITE . VDT_FDINV . " Suite " . VDT_FDNORM . " " . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                // Message d'erreur si première page atteinte et appui sur Retour
                $vdtErrNoPrev = MiniPavi\MiniPaviCli::toG2("Première page !");

                // Message d'erreur si dernière page atteinte et appui sur Suite
                $vdtErrNoNext = MiniPavi\MiniPaviCli::toG2("Dernière page !");

                // 16 lignes maximum par page
                $lines = 16;

                // Initialisation
                $objDisplayPaginatedText = new DisplayPaginatedText($vdtStart, $vdtClearPage, $textFilename, $lTitle, $cTitle, $vdtPreTitle, $lCounter, $cCounter, $vdtPreCounter, $lText, $cText, $maxLengthText, $normalColor, $specialColor, $vdtPreText, $vdtNone, $vdtSuite, $vdtRetour, $vdtSuiteRetour, $vdtErrNoPrev, $vdtErrNoNext, $lines);
                // Exécution
                $r = $objDisplayPaginatedText->process('', $vdt);
            } else {
                // L'utilisateur a déjà l'objet dans son contexte, exécution
                $r = $objDisplayPaginatedText->process(MiniPavi\MiniPaviCli::$fctn, $vdt);
            }
            // Conserver l'objet dans le contexte utilisateur pour le récupérer lors de sa prochaine action
            $context['objDisplayPaginatedText'] = $objDisplayPaginatedText;
            break;
    }

    // URL à appeler lors de la prochaine saisie utilisateur
    $nextPage = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    // Envoi à la passerelle du contenu à afficher, de l'URL du prochain script à appeler,
    // du contexte utilisateur sérialisé, et de l'éventuelle commande à exécuter
    MiniPavi\MiniPaviCli::send($vdt, $nextPage, serialize($context), true, $cmd, $directCall);
} catch (Exception $e) {
    throw new Exception('Erreur MiniPavi: ' . $e->getMessage());
}
exit;
?>