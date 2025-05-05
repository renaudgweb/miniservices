<?php

require "../MiniPaviCli.php";
require "MiniLoto.php";

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
                $vdt .= file_get_contents('Blanc.vdt');
                $vdt .= file_get_contents('Loto.vdt');
                $vdt .= MiniPavi\MiniPaviCli::setPos(5, 18);
                $vdt .= VDT_BGWHITE. VDT_TXTBLACK . VDT_SZDBLHW . date('d/m/Y H:i');
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(24, "   SUITE pour afficher les resultats.   ", VDT_BGBLACK . VDT_TXTWHITE . VDT_BLINK);
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
                $vdt .= file_get_contents('Blanc.vdt');
                $vdt .= file_get_contents('Loto.vdt');
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(24, "      SUITE pour l'Euromillions.        ", VDT_BGBLACK . VDT_TXTWHITE);

                $resultats = getLotoResults();
                if ($resultats) {
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(15, $resultats['date'], VDT_BGWHITE . VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, implode(", ", $resultats['numeros']) . $resultats['chance'], VDT_BGWHITE . VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(17, $resultats['chance'], VDT_BGWHITE . VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(18, ($resultats['joker'] ?? 'Non disponible'), VDT_BGWHITE . VDT_TXTBLUE);
                } else {
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, "Impossible de récupérer les", VDT_BGWHITE . VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(17, "résultats du Loto.", VDT_BGWHITE . VDT_TXTBLUE);
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
                $vdt .= file_get_contents('Blanc.vdt');
                $vdt .= file_get_contents('Loto.vdt');
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(24, "          SUITE pour le Loto.           ", VDT_BGBLACK . VDT_TXTWHITE);

                $resultats = getEuromillionsResults();
                if ($resultats) {
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, $resultats['date'], VDT_BGWHITE . VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(17, implode(", ", $resultats['numeros']) . $resultats['etoiles'], VDT_BGWHITE . VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(18, ($resultats['my_million'] ?? 'Non disponible'), VDT_BGWHITE . VDT_TXTBLUE);
                } else {
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, "Impossible de récupérer les", VDT_BGWHITE . VDT_TXTBLUE);
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered(17, "résultats de l'Euromillions.", VDT_BGWHITE . VDT_TXTBLUE);
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