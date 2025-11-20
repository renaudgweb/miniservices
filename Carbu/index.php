<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 1.2 Novembre 2025
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
                $vdt .= MiniPavi\MiniPaviCli::writeLine0('data.gouv.fr');
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
                if (!isset($content[0]) || empty(trim($content[0]))) {
                    $context['step'] = 'accueil';
                    break;
                }
                // Récupération de la question de l'utilisateur
                $location = $content[0]; // Exemple de ville ou code postal fourni par l'utilisateur
                $location = rtrim($location); // enlever un espace à la fin de la chaîne
                $location = str_replace(' ', '-', $location); // remplacer un espace par un tiret
                $context['location'] = $location;
                $context['step'] = 'pre-reponse';
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Recherche en cours ...', true);
                $directCall = true;
                break 2;

            case 'pre-reponse':
                // 1. Récupération des coordonnées GPS
                try {
                    $coordinates = getCoordinatesFromOpenMeteo($context['location']);
                } catch (Exception $e) {
                    // En cas d'erreur de géocodage, on retourne à l'accueil
                    $context['step'] = 'accueil';
                    break;
                }

                if (empty($coordinates) || !isset($coordinates[0]) || !isset($coordinates[1])) {
                    // Si pas de coordonnées trouvées
                    $context['step'] = 'accueil';
                    break;
                }

                list($latitude, $longitude) = $coordinates;

                // 2. Récupération des stations (avec sécurité)
                try {
                    $nearbyStations = getNearbyStations($latitude, $longitude);
                } catch (Exception $e) {
                    // Si l'API prix-carburants échoue, on ne plante pas le script.
                    // On définit un tableau vide, ce qui déclenchera le message 
                    // "Aucune station trouvée" via la fonction getStationsAsArray
                    error_log("Erreur API Carburants : " . $e->getMessage());
                    $nearbyStations = []; 
                }

                // 3. Génération du texte en mémoire (Plus de fichier stations.txt)
                // On stocke le résultat dans le contexte utilisateur pour le passage au case 'reponse'
                $context['stations_data'] = getStationsAsArray($nearbyStations);

                $context['step'] = 'reponse';

            case 'reponse':
                if ($fctn == 'SOMMAIRE' || $fctn == 'GUIDE') {
                    $context['step'] = 'accueil';
                    $context['reponse'] = '';
                    break;
                }

                // Récupération ou initialisation de l'objet
                $objDisplayPaginatedText = @$context['reponse'];

                if (! ($objDisplayPaginatedText instanceof DisplayPaginatedText)) {

                    // L'utilisateur n'a pas l'objet dans son contexte : il vient d'arriver sur cette rubrique
                    $vdtStart = MiniPavi\MiniPaviCli::clearScreen();

                    // Effacement du texte affiché (zone de page)
                    $vdtClearPage = MiniPavi\MiniPaviCli::setPos(1, 24);
                    $vdtClearPage .= VDT_TXTWHITE . VDT_FDNORM . MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                    for ($i = 0; $i < 23; $i++) {
                        $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(1, 23 - $i);
                        $vdtClearPage .= MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                    }

                    // --- MODIFICATION MULTI-USER ---
                    // On récupère le tableau généré à l'étape précédente
                    // Si vide par sécurité, on met un message par défaut
                    $textData = isset($context['stations_data']) ? $context['stations_data'] : array("Erreur: Aucune donnée récupérée.");
                    
                    // titre Cyan, double hauteur
                    $vdtPreTitle = '';

                    // --- CORRECTION ERREUR PHP 8 ---
                    // On met des entiers (1) et non des chaines vides pour éviter l'erreur "int + string"
                    $lTitle = 1; 
                    $cTitle = 1;
                    // -------------------------------

                    // Position du compteur de page
                    $lCounter = 24;
                    $cCounter = 36;

                    // Compteur de page couleur Cyan (ici Blanc selon votre code original)
                    $vdtPreCounter = VDT_TXTWHITE;

                    // Position début du texte
                    $lText = 1;
                    $cText = 2;

                    // Longueur maximum d'une ligne
                    $maxLengthText = 38;

                    // Couleur normale : blanc
                    $normalColor = VDT_TXTWHITE;

                    // Couleur spéciale : jaune (si ligne commence par #)
                    $specialColor = VDT_TXTYELLOW;

                    // Rien de particulier à afficher avant chaque ligne
                    $vdtPreText = '';

                    // Bas de page si ni Suite ni Retour acceptés
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

                    // Lignes maximum par page
                    $lines = 22;

                    // Initialisation
                    // --- MODIFICATION CONSTRUCTEUR ---
                    // 3ème paramètre : $textData (le tableau) au lieu du nom de fichier
                    // Dernier paramètre : false (pour dire que ce n'est pas un fichier physique)
                    $objDisplayPaginatedText = new DisplayPaginatedText(
                        $vdtStart, 
                        $vdtClearPage, 
                        $textData,     // <-- Ici on passe le tableau
                        $lTitle, 
                        $cTitle, 
                        $vdtPreTitle, 
                        $lCounter, 
                        $cCounter, 
                        $vdtPreCounter, 
                        $lText, 
                        $cText, 
                        $maxLengthText, 
                        $normalColor, 
                        $specialColor, 
                        $vdtPreText, 
                        $vdtNone, 
                        $vdtSuite, 
                        $vdtRetour, 
                        $vdtSuiteRetour, 
                        $vdtErrNoPrev, 
                        $vdtErrNoNext, 
                        $lines,
                        false          // <-- Ici on met false (isFile = false)
                    );

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