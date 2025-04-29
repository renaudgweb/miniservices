<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 0.3 Avril 2025
 *
 * Script via API MistralAI
 * 
 */

require "../MiniPaviCli.php";
require "../DisplayPaginatedText.php";
require "MiniMistral.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
ini_set('display_errors',0);

try {
    MiniPavi\MiniPaviCli::start();

    // Initialisation du contexte utilisateur
    if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
        $context = array('step' => 'accueil');
    } else {
        $context = unserialize(MiniPavi\MiniPaviCli::$context);
    }

    // Initialisation des variables
    $fctn = MiniPavi\MiniPaviCli::$fctn;
    $vdt = ''; // Le contenu vidéotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPavi
    $directCall = false; // Ne pas rappeler le script immédiatement

    // Gestion de la navigation utilisateur
    switch ($context['step']) {
        case 'accueil':
            // Affichage de la demande de question
            $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
            $vdt .= file_get_contents('Mistral.vdt');
            $vdt .= MiniPavi\MiniPaviCli::setPos(2, 11);
            $vdt .= VDT_TXTYELLOW . VDT_FDINV . MiniPavi\MiniPaviCli::toG2(' Demander au Chat : ') . VDT_TXTWHITE;
            $vdt .= MiniPavi\MiniPaviCli::setPos(31, 24);
            $vdt .= MiniPavi\MiniPaviCli::toG2('+') . VDT_TXTWHITE;
            $vdt .= MiniPavi\MiniPaviCli::setPos(33, 24);
            $vdt .= VDT_TXTRED . VDT_FDINV . MiniPavi\MiniPaviCli::toG2(' Envoi ');

        case 'accueil-init-saisie':
            $cmd = MiniPavi\MiniPaviCli::createInputMsgCmd(2, 13, 38, 8, MSK_ENVOI, true, '.', '');
            $context['step'] = 'attente_reponse';
            break;

        case 'attente_reponse':
            if ($fctn == 'SOMMAIRE' || $fctn == 'GUIDE') {
                $context['step'] = 'accueil';
                break;
            }
            // Récupération de la question de l'utilisateur
            $userPrompt = MiniPavi\MiniPaviCli::$content[0];
            // Appel à l'API Mistral AI
            getMistralResponse($userPrompt);
            $textFilename = 'mistral.txt';

            // Affichage de la réponse
            $objDisplayPaginatedText = @$context['attente_reponse'];
            if (! ($objDisplayPaginatedText instanceof DisplayPaginatedText)) {

                // L'utilisateur n'a pas l'objet dans son contexte : il vient d'arriver sur cette rubrique
                $vdtStart = MiniPavi\MiniPaviCli::clearScreen();
                $vdtStart .= file_get_contents('LeChat.vdt');
                // Effacement du texte affiché
                $vdtClearPage = MiniPavi\MiniPaviCli::setPos(2, 24);
                $vdtClearPage .= VDT_TXTWHITE . VDT_FDNORM . MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                for ($i = 0; $i < 19; $i++) {
                    $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(1, 24 - $i);
                    $vdtClearPage .= MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                }
                // titre Cyan, double hauteur
                $vdtPreTitle = '';

                // Position du titre
                $lTitle = 7;
                $cTitle = 2;

                // Position du compteur de page
                $lCounter = 24;
                $cCounter = 36;

                // Compteur de page couleur rouge
                $vdtPreCounter = VDT_TXTYELLOW;

                // Position début du texte
                $lText = 8;
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
                $vdtNone = MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTRED . VDT_FDINV . " Sommaire ";

                // Bas de page si uniquement Suite accepté
                $vdtSuite = MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTRED . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                // Bas de page si uniquement Retour accepté
                $vdtRetour = MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTRED . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                // Bas de page si Suite et Retour acceptés
                $vdtSuiteRetour = MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTRED . VDT_FDINV . " Suite " . VDT_FDNORM . " " . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                // Message d'erreur si première page atteinte et appui sur Retour
                $vdtErrNoPrev = MiniPavi\MiniPaviCli::toG2("Première page !");

                // Message d'erreur si dernière page atteinte et appui sur Suite
                $vdtErrNoNext = MiniPavi\MiniPaviCli::toG2("Dernière page !");

                // 16 lignes maximum par page
                $lines = 15;

                // Initialisation
                $objDisplayPaginatedText = new DisplayPaginatedText($vdtStart, $vdtClearPage, $textFilename, $lTitle, $cTitle, $vdtPreTitle, $lCounter, $cCounter, $vdtPreCounter, $lText, $cText, $maxLengthText, $normalColor, $specialColor, $vdtPreText, $vdtNone, $vdtSuite, $vdtRetour, $vdtSuiteRetour, $vdtErrNoPrev, $vdtErrNoNext, $lines);
                // Exécution
                $r = $objDisplayPaginatedText->process('', $vdt);
            } else {
                // L'utilisateur a déjà l'objet dans son contexte, exécution
                $r = $objDisplayPaginatedText->process(MiniPavi\MiniPaviCli::$fctn, $vdt);
            }
            // Conserver l'objet dans le contexte utilisateur pour le récupérer lors de sa prochaine action
            $context['attente_reponse'] = $objDisplayPaginatedText;
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