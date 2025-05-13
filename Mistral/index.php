<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 1.0 Avril 2025
 *
 * Script via API MistralAI
 * 
 */

require "../MiniPaviCli.php";
require "../DisplayPaginatedText.php";
require "MiniMistral.php";

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
                $vdt .= file_get_contents('Mistral.vdt');
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 11);
                $vdt .= VDT_TXTYELLOW . VDT_FDINV . MiniPavi\MiniPaviCli::toG2(' Demander au Chat : ') . VDT_TXTWHITE;
                $vdt .= MiniPavi\MiniPaviCli::setPos(31, 24);
                $vdt .= MiniPavi\MiniPaviCli::toG2('+') . VDT_TXTWHITE;
                $vdt .= MiniPavi\MiniPaviCli::setPos(33, 24);
                $vdt .= VDT_TXTRED . VDT_FDINV . MiniPavi\MiniPaviCli::toG2(' Envoi ');
                $cmd = MiniPavi\MiniPaviCli::createInputMsgCmd(2, 13, 38, 8, MSK_ENVOI, true, '.', '');

                $context['step'] = 'accueil-init-saisie';
                $directCall = false;
                break 2;

            case 'accueil-init-saisie':
                $vdt = MiniPavi\MiniPaviCli::writeLine0('...', true);
                if (empty($content[0]) || !isset($content[0])) {
                    $context['step'] = 'accueil';
                    break;
                }
                // Récupération de la question de l'utilisateur
                $userPrompt = implode(" ", $content);
                // Appel à l'API Mistral AI
                getMistralResponse($userPrompt);
                $context['step'] = 'reponse';
                $directCall = true;
                break 2;

            case 'reponse':
                if ($fctn == 'SOMMAIRE' || $fctn == 'GUIDE') {
                    $context['step'] = 'accueil';
                    $context['reponse'] = '';
                    break;
                }
                $textFilename = 'mistral.txt';

                // Affichage de la réponse
                $objDisplayPaginatedText = @$context['reponse'];
                if (!($objDisplayPaginatedText instanceof DisplayPaginatedText)) {
                    // L'utilisateur n'a pas encore l'objet, création
                    $vdtStart = MiniPavi\MiniPaviCli::clearScreen();
                    $vdtStart .= file_get_contents('LeChat.vdt');

                    $vdtClearPage = MiniPavi\MiniPaviCli::setPos(1, 24);
                    $vdtClearPage .= VDT_TXTWHITE . VDT_FDNORM . MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                    for ($i = 0; $i < 19; $i++) {
                        $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(1, 24 - $i);
                        $vdtClearPage .= MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                    }

                    $objDisplayPaginatedText = new DisplayPaginatedText(
                        $vdtStart,
                        $vdtClearPage,
                        $textFilename,
                        7, 2, '',            // titre : ligne 7, colonne 2, pas de préfixe
                        24, 36, VDT_TXTYELLOW, // compteur de page ligne 24 col 36 en jaune
                        8, 2, 38,            // texte ligne 8 col 2, 38 caractères max
                        VDT_TXTWHITE,        // couleur normale
                        VDT_TXTYELLOW,       // couleur spéciale (#)
                        '',                  // rien avant chaque ligne
                        MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTRED . VDT_FDINV . " Sommaire ",
                        MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTRED . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ",
                        MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTRED . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ",
                        MiniPavi\MiniPaviCli::setPos(3, 24) . VDT_TXTRED . VDT_FDINV . " Suite " . VDT_FDNORM . " " . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ",
                        MiniPavi\MiniPaviCli::toG2("Première page !"),
                        MiniPavi\MiniPaviCli::toG2("Dernière page !"),
                        15 // 15 lignes par page
                    );
                }

                // Exécution
                $r = $objDisplayPaginatedText->process(MiniPavi\MiniPaviCli::$fctn, $vdt);

                // Sauvegarde dans le contexte
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