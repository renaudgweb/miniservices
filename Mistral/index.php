<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 0.1 Avril 2025
 *
 * Script via API MistralAI
 * 
 */

require "../MiniPaviCli.php";
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
    $vdt = ''; // Le contenu vidéotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPavi
    $directCall = false; // Ne pas rappeler le script immédiatement

    // Gestion de la navigation utilisateur
    switch ($context['step']) {
        case 'question':
            // Affichage de la demande de question
            $vdt = MiniPavi\MiniPaviCli::clearScreen();
            $vdt .= "Posez votre question :\n";
            $cmd = MiniPavi\MiniPaviCli::createInputMsgCmd(1, 3, 40, 3, MSK_ENVOI, true, ' ', '');
            $context['step'] = 'attente_reponse';
            break;

        case 'attente_reponse':
            // Récupération de la question de l'utilisateur
            $userPrompt = MiniPavi\MiniPaviCli::$content[0];

            // Appel à l'API Mistral AI
            $reponse = getMistralResponse($userPrompt);

            // Affichage de la réponse
            $vdt = MiniPavi\MiniPaviCli::clearScreen();
            $vdt .= "Réponse :\n";
            $vdt .= wordwrap($reponse, 40, "\n"); // Ajuste la longueur des lignes
            $context['step'] = 'question'; // Revenir à l'étape de question
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