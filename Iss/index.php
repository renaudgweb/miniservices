<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 1.0 Mai 2025
 *
 * Script pour l'ISS
 *
 */

require "../MiniPaviCli.php";
require "MiniIss.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
ini_set('display_errors', 0);

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

    // Définir les paramètres régionaux en français
    $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
    $formatter->setPattern('d MMMM yyyy \'à\' HH:mm');

    while (true) {
        // Gestion de la navigation utilisateur
        switch ($context['step']) {
            case 'accueil':
                // Affichage de la page d'accueil
                $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
                $vdt .= file_get_contents('iss.vdt');
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 1) . VDT_BGBLUE . VDT_TXTCYAN . $formatter->format(new DateTime());
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 3) . VDT_BGBLUE . VDT_TXTCYAN . "3615 ISS";
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(16, "La porte vers les étoiles...", VDT_TXTWHITE);
                $vdt .= MiniPavi\MiniPaviCli::setPos(3, 24);
                $vdt .= VDT_BGCYAN . VDT_TXTBLACK . VDT_BLINK . " SUITE ";
                $vdt .= MiniPavi\MiniPaviCli::setPos(10, 24);
                $vdt .= VDT_BGBLACK . VDT_TXTWHITE . " pour le nom des spationautes.";

                $context['step'] = 'accueil-saisie';
                $directCall = false;
                break 2;

            case 'accueil-saisie':
                if ($fctn == 'SUITE') {
                    $context['step'] = 'init-iss-astros';
                    break;
                }
                if ($fctn == 'SOMMAIRE') {
                    $context['step'] = 'accueil';
                    break;
                }
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Tapez sur SUITE !');
                $directCall = false;
                break 2;

            case 'init-iss-astros':
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Recherche des noms en cours ...', true);
                $context['step'] = 'pre-iss-astros';
                $directCall = true;
                break 2;

            case 'pre-iss-astros':
                $astronautsData = getAstronauts();
                $context['number'] = $astronautsData['number'];
                $context['people'] = $astronautsData['people'];

                $context['step'] = 'iss-astros';

            case 'iss-astros':
                // Affichage des spationautes
                $vdt = MiniPavi\MiniPaviCli::clearScreen();
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 4) . VDT_TXTWHITE . "Il y a actuellement " . $context['number'] . " spationautes en orbite :";

                $counter = 7;
                foreach ($context['people'] as $astronaut) {
                    $vdt .= MiniPavi\MiniPaviCli::setPos(4, $counter) . "- ";
                    $vdt .= MiniPavi\MiniPaviCli::toG2($astronaut['name']);
                    $vdt .= " -> " . $astronaut['craft'];
                    $counter += 1;
                }
                $vdt .= MiniPavi\MiniPaviCli::setPos(18, 24) . VDT_TXTWHITE . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

                $context['step'] = 'iss-suite';
                $directCall = false;
                break 2;

            case 'iss-suite':
                if ($fctn == 'SUITE') {
                    $context['step'] = 'init-iss-location';
                    break;
                }
                if ($fctn == 'SOMMAIRE') {
                    $context['step'] = 'accueil';
                    break;
                }
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Tapez sur SUITE !');
                $directCall = false;
                break 2;

            case 'init-iss-location':
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Recherche de la position en cours ...', true);
                $context['step'] = 'pre-iss-location';
                $directCall = true;
                break 2;

            case 'pre-iss-location':
                $issLocation = getLocation();
                $latitude = $issLocation['iss_position']['latitude'];
                $longitude = $issLocation['iss_position']['longitude'];
                $mistralResponse = getPosition($latitude, $longitude);

                $latitudeDMS = convertDecimalToDMS($latitude, true);
                $longitudeDMS = convertDecimalToDMS($longitude, false);

                $context['latitude'] = $latitudeDMS;
                $context['longitude'] = $longitudeDMS;
                $context['mistralResponse'] = $mistralResponse;

                $context['step'] = 'iss-location';

            case 'iss-location':
                // Affichage de la position de l'ISS
                $vdt = MiniPavi\MiniPaviCli::clearScreen();
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 3);
                $vdt .= MiniPavi\MiniPaviCli::toG2($context['mistralResponse']);
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 20) . VDT_TXTWHITE;
                $vdt .= MiniPavi\MiniPaviCli::toG2($context['latitude'] . ", " . $context['longitude']);
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 21) . VDT_TXTWHITE . "7,66 km/s";
                $vdt .= MiniPavi\MiniPaviCli::setPos(2, 22) . VDT_TXTWHITE . "408 km";
                $vdt .= MiniPavi\MiniPaviCli::setPos(18, 24) . VDT_TXTWHITE . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";
                $vdt .= MiniPavi\MiniPaviCli::writeLine0("Le " . $formatter->format(new DateTime()));

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