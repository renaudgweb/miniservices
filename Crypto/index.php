<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 1.0 Avril 2025
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

    // Initialisation du contexte utilisateur
    if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
        $context = array('step' => 'accueil');
    } else {
        $context = unserialize(MiniPavi\MiniPaviCli::$context);
    }

    // Récupération des prix des cryptomonnaies
    $cryptoPrices = getPrices();

    // Initialisation des variables
    $vdt = ''; // Le contenu vidéotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPavi
    $directCall = false; // Ne pas rappeler le script immédiatement

    // Définir les paramètres régionaux en français
    $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
    $formatter->setPattern('d MMMM yyyy \'à\' HH:mm');

    // Gestion de la navigation utilisateur
    switch ($context['step']) {
        case 'accueil':
            // Affichage de la page d'accueil
            $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
            $vdt .= file_get_contents('3615CryptoMoney.vdt');
            $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, "S'initier n'est pas un délit.", VDT_TXTWHITE);
            $vdt .= MiniPavi\MiniPaviCli::writeCentered(24, "SUITE pour plus d'informations.", VDT_BLINK);

            $context['step'] = 'bitcoin';
            break;

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
            $vdt .= MiniPavi\MiniPaviCli::writeCentered(24, "SUITE pour plus d'informations.");

            // Vérification des touches
            if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE' || MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
                $context['step'] = 'accueil';
            }
            if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
                $context['step'] = 'bitcoin';
            }
            if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
                $context['step'] = 'affichage_prix';
            }
            break;

        case 'affichage_prix':
            // Affichage des prix des cryptomonnaies
            $vdt = MiniPavi\MiniPaviCli::clearScreen();

            $counter = 4; // Initialisez le compteur
            foreach ($cryptoPrices as $crypto) {
                $vdt .= MiniPavi\MiniPaviCli::setPos(4, $counter,) . $crypto['titre'] . ": " . $crypto['desc'];
                $vdt .= MiniPavi\MiniPaviCli::writeCentered($counter + 1, "------------------------------");
                $counter += 2;
            }

            $vdt .= MiniPavi\MiniPaviCli::writeCentered($counter + 4, "SOMMAIRE pour revenir à l'accueil.");
            $vdt .= MiniPavi\MiniPaviCli::writeLine0($formatter->format(new DateTime()));

            // Vérification des touches
            if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE' || MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
                $context['step'] = 'accueil';
            }
            if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
                $context['step'] = 'bitcoin';
            }
            if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
                $context['step'] = 'affichage_prix';
            }
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