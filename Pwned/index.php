<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 1.0 Juin 2025
 *
 * Script via API haveibeenpwned.com
 *
 */

require "../MiniPaviCli.php";
require "MiniPwned.php";

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
                $vdt .= file_get_contents('Pwned.vdt');
                $vdt .= MiniPavi\MiniPaviCli::setPos(10, 5);
                $vdt .= VDT_TXTWHITE . VDT_SZDBLHW . "Have I Been";
                $vdt .= MiniPavi\MiniPaviCli::setPos(20, 9);
                $vdt .= VDT_TXTWHITE . VDT_SZDBLHW . "Pwned";
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 16);
                $vdt .= VDT_TXTWHITE . VDT_FDINV . " Mot de passe : ";
                $vdt .= MiniPavi\MiniPaviCli::setPos(31, 24);
                $vdt .= MiniPavi\MiniPaviCli::toG2('+') . VDT_TXTWHITE;
                $vdt .= MiniPavi\MiniPaviCli::setPos(33, 24);
                $vdt .= VDT_TXTWHITE . VDT_FDINV . MiniPavi\MiniPaviCli::toG2(' Envoi ');
                $cmd = MiniPavi\MiniPaviCli::createInputTxtCmd(2, 18, 38, MSK_ENVOI, true, '.', '');

                $context['step'] = 'accueil-init-saisie';
                $directCall = false;
                break 2;

            case 'accueil-init-saisie':
                if (!isset($content[0]) || empty($content[0])) {
                    $context['step'] = 'accueil';
                    break;
                }
                // Récupération de la question de l'utilisateur
                $password = $content[0]; // Exemple de mot de passe fourni par l'utilisateur
                $context['password'] = $password;
                $context['step'] = 'pre-reponse';
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Recherche en cours ...', true);
                $directCall = true;
                break 2;

            case 'pre-reponse':
                $count = checkPasswordPwned($context['password']);
                $context['count'] = $count;
                $context['step'] = 'reponse';
                $directCall = true;
                break 2;

            case 'reponse':
                if ($fctn == 'SOMMAIRE' || $fctn == 'GUIDE') {
                    $context['step'] = 'accueil';
                    break;
                }

                $vdt = MiniPavi\MiniPaviCli::setPos(1, 24);
                    $vdt .= VDT_TXTWHITE . VDT_FDNORM . MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                for ($i = 0; $i < 14; $i++) {
                    $vdt .= MiniPavi\MiniPaviCli::setPos(1, 23 - $i);
                    $vdt .= MiniPavi\MiniPaviCli::repeatChar(' ', 39);
                }

                if ($context['count'] > 0) {
                    $vdt .= MiniPavi\MiniPaviCli::writeLine0('Oh non - pwned !', false);
                    $vdt .= MiniPavi\MiniPaviCli::setPos(2, 13);
                    $vdt .= VDT_TXTRED . VDT_FDINV;
                    $vdt .= MiniPavi\MiniPaviCli::toG2("Ce mot de passe a été vu " . $context['count'] . " fois auparavant dans des violations de données !");
                    $vdt .= MiniPavi\MiniPaviCli::setPos(2, 17);
                    $vdt .= VDT_TXTRED . VDT_FDINV;
                    $vdt .= MiniPavi\MiniPaviCli::toG2("Ce mot de passe est déjà apparu dans une violation de données et ne devrait jamais être utilisé. Si vous l'avez déjà utilisé quelque part, changez-le immédiatement !");
                } else {
                    $vdt .= MiniPavi\MiniPaviCli::writeLine0('Good news - no pwnage found !', false);
                    $vdt .= MiniPavi\MiniPaviCli::setPos(2, 13);
                    $vdt .= VDT_TXTGREEN . VDT_FDINV;
                    $vdt .= MiniPavi\MiniPaviCli::toG2("Ce mot de passe n'a pas été trouvé parmi les mots de passe compromis chargés dans Have I Been Pwned. Cela ne signifie pas nécessairement que c'est un bon mot de passe, mais simplement qu'il n'est pas indexé sur le site.");
                }

                $vdt .= MiniPavi\MiniPaviCli::setPos(30, 24);
                $vdt .= VDT_TXTWHITE . VDT_FDINV . " Sommaire ";

                $context['step'] = 'accueil-init-saisie';
                $directCall = false;
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