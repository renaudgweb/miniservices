<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 1.1 Mai 2025
 *
 * Script via API Coingecko
 * 
 */

require "../MiniPaviCli.php";
require "MiniCrypto.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
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

    // Initialisation des variables
    $vdt = ''; // Le contenu vidéotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPavi
    $directCall = false; // Ne pas rappeler le script immédiatement

    // Récupération des prix des cryptomonnaies
    $cryptoPrices = getPrices();
    // Définir les paramètres régionaux en français
    $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
    $formatter->setPattern('d MMMM yyyy \'à\' HH:mm');

    while (true) {
        // Gestion de la navigation utilisateur
        switch ($context['step']) {
            case 'accueil':
                // Affichage de la page d'accueil
                $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
                $vdt .= file_get_contents('3615CryptoMoney.vdt');
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, "S'initier n'est pas un délit.", VDT_TXTWHITE);
                $vdt .= MiniPavi\MiniPaviCli::setPos(4, 24);
                $vdt .= VDT_BGCYAN . VDT_TXTBLACK . VDT_BLINK . " SUITE ";
                $vdt .= MiniPavi\MiniPaviCli::setPos(11, 24);
                $vdt .= VDT_BGBLACK . VDT_TXTWHITE . " pour le cours du Bitcoin.";

                $context['step'] = 'accueil-saisie';
                $directCall = false;
                break 2;

            case 'accueil-saisie':
                if ($fctn == 'SUITE') {
                    $context['step'] = 'bitcoin';
                    break;
                }
                if ($fctn == 'SOMMAIRE') {
                    $context['step'] = 'accueil';
                    break;
                }
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Tapez sur SUITE !');
                $directCall = false;
                break 2;

            case 'bitcoin':
                // Affichage de la page bitcoin avec uniquement le prix du Bitcoin
                $vdt = MiniPavi\MiniPaviCli::clearScreen();
                $vdt .= file_get_contents('btc.vdt');

                // Recherche des informations sur le Bitcoin
                $bitcoinPrice = "";
                foreach ($cryptoPrices as $crypto) {
                    if ($crypto['titre'] == ucfirst('bitcoin')) {
                        $bitcoinPrice = $crypto['desc'];
                        break;
                    }
                }
                // Affichage du prix du Bitcoin
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(12, $bitcoinPrice, VDT_TXTYELLOW . VDT_SZDBLH . VDT_FDINV);
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(15, "Prix du Bitcoin mis à jour le", VDT_TXTWHITE);
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, $formatter->format(new DateTime()));
                $vdt .= MiniPavi\MiniPaviCli::setPos(18, 24) . VDT_TXTYELLOW . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                $context['step'] = 'bitcoin-suite';
                $directCall = false;
                break 2;

            case 'bitcoin-suite':
                if ($fctn == 'SUITE') {
                    $context['step'] = 'affichage-prix';
                    break;
                }
                if ($fctn == 'SOMMAIRE') {
                    $context['step'] = 'accueil';
                    break;
                }
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Tapez sur SUITE !');
                $directCall = false;
                break 2;

            case 'affichage-prix':
                // Affichage des prix des cryptomonnaies
                $vdt = MiniPavi\MiniPaviCli::clearScreen();

                $counter = 4; // Initialisez le compteur
                foreach ($cryptoPrices as $crypto) {
                    $vdt .= MiniPavi\MiniPaviCli::setPos(4, $counter,) . $crypto['titre'] . ": " . $crypto['desc'];
                    $vdt .= MiniPavi\MiniPaviCli::writeCentered($counter + 1, "------------------------------");
                    $counter += 2;
                }
                $vdt .= MiniPavi\MiniPaviCli::setPos(18, 24) . VDT_TXTWHITE . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";
                $vdt .= MiniPavi\MiniPaviCli::writeLine0($formatter->format(new DateTime()));

                $context['step'] = 'accueil-saisie';
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