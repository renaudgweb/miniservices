<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 1.0 Avril 2025
 *
 * Script via API data.economie.gouv.fr
 * 
 */

require "../MiniPaviCli.php";
require "../DisplayPaginatedText.php";
require "MiniCarbu.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR|E_WARNING);
ini_set('display_errors',0);

try {
    MiniPavi\MiniPaviCli::start();

    // Initialisation du contexte utilisateur
    if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
        $context = array('step' => 'accueil');
    } else {
        if (MiniPavi\MiniPaviCli::$fctn == 'FIN') {
            exit;
        }
        $context = unserialize(MiniPavi\MiniPaviCli::$context);
        $fctn = MiniPavi\MiniPaviCli::$fctn;
        $content = MiniPavi\MiniPaviCli::$content;
    }
    // Initialisation des variables
    $vdt = ''; // Le contenu vidéotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPavi
    $directCall = false; // Ne pas rappeler le script immédiatement

    while (true) {
        // Gestion de la navigation utilisateur
        switch ($context['step']) {
            case 'accueil':
                // Affichage de la demande de question
                $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
                $vdt .= file_get_contents('Carbu.vdt');
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 18);
                $vdt .= VDT_TXTWHITE . VDT_FDINV . " Ville ou code postal : ";
                $vdt .= MiniPavi\MiniPaviCli::setPos(31, 24);
                $vdt .= MiniPavi\MiniPaviCli::toG2('+') . VDT_TXTWHITE;
                $vdt .= MiniPavi\MiniPaviCli::setPos(33, 24);
                $vdt .= VDT_TXTWHITE . VDT_FDINV . MiniPavi\MiniPaviCli::toG2(' Envoi ');
                $cmd = MiniPavi\MiniPaviCli::createInputTxtCmd(2, 20, 38, MSK_ENVOI, true, '.', '');

                $context['step'] = 'accueil-init-saisie';
                $directCall = false;
                break 2;

            case 'accueil-init-saisie':
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Recherche en cours ...', true);
                // Récupération de la question de l'utilisateur
                $location = $content[0]; // Exemple de ville ou code postal fourni par l'utilisateur
                $location = rtrim($location); // enlever un espace à la fin de la chaîne
                $location = str_replace(' ', '-', $location); // remplacer un espace par un tiret
                $coordinates = getCoordinatesFromOpenMeteo($location);
                if (empty($coordinates) || !isset($coordinates[0]) || !isset($coordinates[1])) {
                    $context['step'] = 'accueil';
                    break;
                }
                list($latitude, $longitude) = $coordinates;
                $nearbyStations = getNearbyStations($latitude, $longitude);
                displayFuelPrices($nearbyStations, 'stations.txt');

                $context['step'] = 'reponse';
                $directCall = true;
                break 2;

            case 'reponse':
                if ($fctn == 'SOMMAIRE' || $fctn == 'GUIDE') {
                    $context['step'] = 'accueil';
                    $context['reponse'] = '';
                    break;
                }
                // Affichage de la réponse
                $objDisplayPaginatedText = @$context['reponse'];
                if (! ($objDisplayPaginatedText instanceof DisplayPaginatedText)) {

                    // L'utilisateur n'a pas l'objet dans son contexte : il vient d'arriver sur cette rubrique
                    $vdtStart = MiniPavi\MiniPaviCli::clearScreen();
                    // Effacement du texte affiché
                    $vdtClearPage = MiniPavi\MiniPaviCli::setPos(1, 23);
                    $vdtClearPage .= VDT_TXTWHITE . VDT_FDNORM . MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                    for ($i = 0; $i < 23; $i++) {
                        $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(1, 24 - $i);
                        $vdtClearPage .= MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                    }

                    $textFilename = 'stations.txt';

                    // titre Cyan, double hauteur
                    $vdtPreTitle = '';

                    // Position du titre
                    $lTitle = 3;
                    $cTitle = 2;
                    // Position du compteur de page
                    $lCounter = 24;
                    $cCounter = 36;

                    // Compteur de page couleur Cyan
                    $vdtPreCounter = VDT_TXTWHITE;

                    // Position début du texte
                    $lText = 4;
                    $cText = 2;

                    // Longueur maximum d'une ligne
                    $maxLengthText = 38;

                    // Couleur normale : blanc
                    $normalColor = VDT_TXTWHITE;

                    // Couleur spéciale : jaune
                    $specialColor = VDT_TXTYELLOW;

                    // Rien de particulier à afficher avant chaque ligne
                    $vdtPreText = '';

                    // Bas de page si ni Suite ni Retour acceptés (Sommaire n'est pas géré par l'objet, mais directement par le script)
                    $vdtNone = MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTWHITE . VDT_FDINV . " Sommaire ";

                    // Bas de page si uniquement Suite accepté
                    $vdtSuite = MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTWHITE . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                    // Bas de page si uniquement Retour accepté
                    $vdtRetour = MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTWHITE . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                    // Bas de page si Suite et Retour acceptés
                    $vdtSuiteRetour = MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTWHITE . VDT_FDINV . " Suite " . VDT_FDNORM . " " . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

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
                $context['reponse'] = $objDisplayPaginatedText;
                break 2;
        }
    }
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        $prot = 'https';
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        $prot = 'https';
    } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        $prot = 'https';
    } elseif (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443) {
        $prot = 'https';
    } else {
        $prot = 'http';
    }
    $nextPage = $prot . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    MiniPavi\MiniPaviCli::send($vdt, $nextPage, serialize($context), true, $cmd, $directCall);
} catch (Exception $e) {
    throw new Exception('Erreur MiniPavi ' . $e->getMessage());
}
exit;
?>