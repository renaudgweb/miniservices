<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 1.0 Mai 2025
 *
 * 
 */

require "../MiniPaviCli.php";
require "MiniLoto.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR|E_WARNING);
ini_set('display_errors',0);

try {
    MiniPavi\MiniPaviCli::start();
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
    $vdt = '';
    $cmd = null;
    while (true) {
        switch ($context['step']) {
            case 'accueil':
                $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
                $vdt .= file_get_contents('Loto.vdt');
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(14, "LOTO NATIONAL : facile, pas cher....", VDT_BGWHITE. VDT_TXTRED);
                $vdt .= MiniPavi\MiniPaviCli::setPos(5, 19);
                $vdt .= VDT_TXTBLUE . VDT_SZDBLHW . date('d/m/Y H:i');
                $vdt .= MiniPavi\MiniPaviCli::setPos(3, 24);
                $vdt .= VDT_BGRED . VDT_TXTWHITE . VDT_BLINK . " SUITE ";
                $vdt .= MiniPavi\MiniPaviCli::setPos(10, 24);
                $vdt .= VDT_BGBLUE . VDT_TXTWHITE . " pour afficher les resultats.";
                $context['step'] = 'accueil-traitement-saisie';
                $directCall = false;
                break 2;

            case 'accueil-traitement-saisie':
                if ($fctn == 'SUITE') {
                    $context['step'] = 'menu-loto';
                    break;
                }
                if ($fctn == 'SOMMAIRE') {
                    $context['step'] = 'accueil';
                    break;
                }
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Tapez sur Suite !');
                $directCall = false;
                break 2;

            case 'menu-loto':
                $vdt = MiniPavi\MiniPaviCli::clearScreen();
                $vdt .= file_get_contents('Loto.vdt');
                $vdt .= MiniPavi\MiniPaviCli::setPos(18, 24) . VDT_TXTRED . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                $resultats = getLotoResultat();
                if ($resultats) {
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(14, "Loto du " . $resultats['date'], VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, $resultats['jackpot'], VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(19, VDT_BGBLUE . implode(" - ", $resultats['numeros']) . " * " . $resultats['chance'], VDT_BGBLUE . VDT_TXTWHITE . VDT_SZDBLH);
                } else {
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, "Impossible de récupérer les", VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(17, "résultats du Loto.", VDT_TXTBLUE);
                }

                $context['step'] = 'menu-suite';
                $directCall = false;
                break 2;

            case 'menu-suite':
                if ($fctn == 'SUITE') {
                    $context['step'] = 'menu-euro';
                    break;
                }
                if ($fctn == 'SOMMAIRE') {
                    $context['step'] = 'accueil';
                    break;
                }
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Tapez sur Suite !');
                $directCall = false;
                break 2;

            case 'menu-euro':
                $vdt = MiniPavi\MiniPaviCli::clearScreen();
                $vdt .= file_get_contents('Loto.vdt');
                $vdt .= MiniPavi\MiniPaviCli::setPos(18, 24) . VDT_TXTRED . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                $resultats = getEuromillionsResultat();
                if ($resultats) {
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(14, "Euromillions du " . $resultats['date'], VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, $resultats['jackpot'], VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(19, VDT_BGBLUE . implode(" - ", $resultats['numeros']) . " * " . implode(" - ", $resultats['chances']), VDT_BGBLUE . VDT_TXTWHITE . VDT_SZDBLH);
                } else {
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, "Impossible de récupérer les", VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(17, "résultats de l'Euromillions.", VDT_TXTBLUE);
                }

                $context['step'] = 'accueil-traitement-saisie';
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