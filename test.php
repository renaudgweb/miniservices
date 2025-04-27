<?php

require "MiniPaviCli.php"; // Inclusion de la librairie

try {
    MiniPavi\MiniPaviCli::start();
    if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
        // C'est une nouvelle connexion.
        // Ici, vous pouvez initialiser ce que vous souhaitez
        // Par exemple, un contexte utilisateur contenant une variable "step" avec la
        // valeur "accueil"
        // Cette variable nous servira pour savoir quelle partie du script exécuter.
        // Toutes les variables du contexte seront dans un tableau associatif
        // clé/valeur qui sera ensuite "serializé" avec
        // la fonction serialize() de PHP
        $context = array('step' => 'accueil');
    } else {
        // Ce n'est pas une nouvelle connexion
        // Une touche de fonction a été saisie ou un évènement a eu lieu
        if (MiniPavi\MiniPaviCli::$fctn == 'FIN') {
            // C'est la déconnexion de l'utilisateur
            // On peut en profiter pour effectuer des tâches nécessaires
            // lors de la déconnexion
            // Attention: cet évènement peut être appelé plusieurs fois de
            // suite
            exit;
        }
        // On récupère le contexte utilisateur.
        $context = unserialize(MiniPavi\MiniPaviCli::$context);
        // On récupère la touche de fonction
        $fctn = MiniPavi\MiniPaviCli::$fctn;
        // On récupère la saisie utilisateur
        $content = MiniPavi\MiniPaviCli::$content;
    }
    // On initialise quelques variables
    $vdt = ''; // Le contenu videotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPAVI. Par défaut, aucune.
    while (true) {
        // On exécute la partie du script qui correspond à la valeur de la variable
        // "step"
        switch ($context['step']) {
            case 'accueil':
                // Accueil du service : on affiche une page et on attend que
                // l'utilisateur tape sur SUITE
                // On remplit la variable $vdt au fur et à mesure
                // Effacement de l'écran et suppression de l'echo local du
                // Minitel au cas où
                $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
                // Récupération du contenu d'une page videotext, que l'on ajoute
                // à l'effacement d'écran précédent
                // Le fichier de la page doit exister et être lisible depuis ce
                // script
                $vdt .= file_get_contents('mapage.vdt');
                // On affiche la date et l'heure en ligne 10, colonne 5,
                // en Rouge
                $vdt .= MiniPavi\MiniPaviCli::setPos(5, 10);
                $vdt .= VDT_TXTRED . date('d/m/Y H:i');
                // Lors du prochain appel du script par la passerelle, on
                // exécutera la partie 'accueil-traitement-saisie'
                // pour traiter la saisie de l'utilisateur
                $context['step'] = 'accueil-traitement-saisie';
                // On attend une saisie utilisateur : on ne rappelle pas le
                // script immédiatement
                $directCall = false;
                break 2; // On sort du bloc "switch" et "while"

            case 'accueil-traitement-saisie':
                // On n'accepte qu'un appui sur la touche SUITE...
                if ($fctn == 'SUITE') {
                    // L'utilisateur a appuyé sur 'SUITE'.
                    // On veut maintenant exécuter la partie du script 'menu'
                    $context['step'] = 'menu';
                    // On continue le script dans sa partie 'menu': on sort
                    // du bloc 'switch', mais on reste dans le bloc 'while'
                    break;
                }
                // sinon, on lui affiche un message en ligne "0"
                // et on attend de nouveau qu'il appuie sur "SUITE"
                $vdt = MiniPavi\MiniPaviCli::writeLine0('Tapez sur Suite !');
                // On ne modifie pas la valeur de 'step', qui a déjà la valeur
                // 'accueil-traitement-saisie'
                // On attend une saisie utilisateur : on ne rappelle pas le
                // script immédiatement
                $directCall = false;
                break 2; // On sort du bloc "switch" et "while"

            case 'menu':
                // Effacement de l'écran
                $vdt = MiniPavi\MiniPaviCli::clearScreen();
                // On positionne le curseur ligne 24, colonne 1
                // et on attend que l'utilisateur saisisse quelque chose de
                // maximum 20 caractères.
                // La zone de saisie est représentée par des '.'
                // L'utilisateur pourra valider sa saisie avec les touches ENVOI
                // ou SOMMAIRE
                // On utilise ici la fonction "createInputTxtCmd"
                $cmd = MiniPavi\MiniPaviCli::createInputTxtCmd(1, 24, 20, MSK_ENVOI | MSK_SOMMAIRE, true, '.', '');
                // Lors du prochain appel du script par la passerelle, on
                // exécutera la partie 'menu-saisie'
                // pour traiter la saisie de l'utilisateur
                $context['step'] = 'menu-saisie';
                // On attend une saisie utilisateur : on ne rappelle pas le
                // script immédiatement
                $directCall = false;
                break 2; // On sort du bloc "switch" et "while"

            case 'menu-saisie':
                // L'utilisateur a donc validé sa saisie avec ENVOI ou SOMMAIRE
                // Si c'est ENVOI : on lui affiche ce qu'il a tapé
                // Si c'est SOMMAIRE : on revient à l'accueil
                if ($fctn == 'SOMMAIRE') {
                    // L'utilisateur a appuyé sur 'SOMMAIRE'.
                    // On veut maintenant exécuter la partie du script
                    // 'accueil'
                    $context['step'] = 'accueil';
                    // On continue le script dans sa partie 'accueil': on
                    // sort du bloc 'switch', mais on reste dans le bloc
                    // 'while'
                    break;
                }
                // C'est donc la touche ENVOI qui a été tapée
                // Effacement de l'écran
                $vdt = MiniPavi\MiniPaviCli::clearScreen();
                // A la ligne 12, on affiche, centré, en magenta, le texte 'Vous
                // avez tapé'
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(12, "Vous avez tapé", VDT_TXTMAGENTA);
                // A la ligne 14, on affiche, centré, en jaune, le texte saisi
                // par l'utilisateur
                $vdt .= MiniPavi\MiniPaviCli::writeCentered(14, $content[0], VDT_TXTYELLOW);
                // Maintenant, on attend qu'il tape n'importe quelle touche de
                // fonction pour revenir à la saisie d'un texte
                // (partie "menu" du script)
                $context['step'] = 'menu';
                // On attend une saisie utilisateur : on ne rappelle pas le
                // script immédiatement
                $directCall = false;
                break 2; // On sort du bloc "switch" et "while"
        }
    }
    // Url à appeler lors de la prochaine saisie utilisateur (ou sans attendre si
    // directCall=true)
    // On reprend l'Url du script courant que l'on va placer dans la variable $nextPage
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
    // On envoie à la passerelle le contenu à afficher ($vdt), l'url du prochain script
    // à appeler ($nextPage)
    // le contexte utilisateur sérialisé ($context), l'éventuelle commande à exécuter
    // On active l'echo de caractères pour que l'utilisateur voit ce qu'il tape
    // Si $directCall = true, le script sera appelé immédiatement
    MiniPavi\MiniPaviCli::send($vdt, $nextPage, serialize($context), true, $cmd, $directCall);
} catch (Exception $e) {
    throw new Exception('Erreur MiniPavi ' . $e->getMessage());
}
exit;
?>




-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-




<?php


// Classe DisplayPaginatedText


case 10:
    if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
        $step = 0; // Retour au sommaire
        break;
    }
    // Récupération de l'éventuel objet existant dans le contexte utilisateur
    $objDisplayPaginatedText = @$context['objDisplayPaginatedText'];
    if (!($objDisplayPaginatedText instanceof DisplayPaginatedText)) {
        // L'utilisateur n'a pas l'objet dans son contexte : il vient d'arriver sur cette rubrique
        // Fond de page
        $vdtStart = MiniPavi\MiniPaviCli::clearScreen();
        $vdtStart .= file_get_contents('fond-de-page.vdt');
        // Effacement du texte affiché
        $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(3, 23);
        $vdtClearPage .= VDT_TXTBLACK . VDT_FDNORM . MiniPavi\MiniPaviCli::repeatChar(' ', 33);
        for ($i = 0; $i < 18; $i++) {
            $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(1, 21 - $i);
            $vdtClearPage .= VDT_BGBLUE . MiniPavi\MiniPaviCli::repeatChar(' ', 33);
        }
        // fichier contenant le texte
        $textFilename = 'le-texte.txt';
        // titre Cyan , double hauteur
        $vdtPreTitle = VDT_TXTCYAN . VDT_SZDBLH;
        // Position du titre
        $lTitle = 2;
        $cTitle = 11;
        // Position du compteur de page
        $lCounter = 21;
        $cCounter = 35;
        // Compteur de page couleur Cyan
        $vdtPreCounter = VDT_TXTCYAN;
        // Position début du texte
        $lText = 5;
        $cText = 2;
        // Longueur maximum d'une ligne
        $maxLengthText = 38;
        // Couleur normale : jaune
        $normalColor = VDT_TXTYELLOW;
        // Couleur spéciale : blanc
        $specialColor = VDT_TXTWHITE;
        // Rien de particulier à afficher avant chaque ligne
        $vdtPreText = '';
        // Bas de page si ni Suite ni Retour acceptés (Sommaire n'est pas gérée par l'objet, mais directement par le script)
        $vdtNone = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTBLACK . VDT_FDINV . " Sommaire ";
        // Bas de page si uniquement Suite accepté
        $vdtSuite = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTBLACK . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";
        // Bas de page si uniquement Retour accepté
        $vdtRetour = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTBLACK . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";
        // Bas de page si Suite et Retour acceptés
        $vdtSuiteRetour = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTBLACK . VDT_FDINV . " Suite " . VDT_FDNORM . " " . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";
        // Message d'erreur si première page atteinte et appui sur Retour
        $vdtErrNoPrev = MiniPavi\MiniPaviCli::toG2("Première page !");
        // Message d'erreur si dernière page atteinte et appui sur Suite
        $vdtErrNoNext = MiniPavi\MiniPaviCli::toG2("Dernière page !");
        // 16 lignes maximum par page
        $lines = 16;
        // initialisation
        $objDisplayPaginatedText = new DisplayPaginatedText(
            $vdtStart, $vdtClearPage, $textFilename, $lTitle, $cTitle, $vdtPreTitle,
            $lCounter, $cCounter, $vdtPreCounter, $lText, $cText,
            $maxLengthText, $normalColor, $specialColor, $vdtPreText, $vdtNone, $vdtSuite, $vdtRetour,
            $vdtSuiteRetour, $vdtErrNoPrev, $vdtErrNoNext, $lines
        );
        // Execution
        $r = $objDisplayPaginatedText->process('', $vdt);
    } else {
        // L'utilisateur a déjà l'objet dans son contexte, execution
        $r = $objDisplayPaginatedText->process(MiniPavi\MiniPaviCli::$fctn, $vdt);
    }
    // A ce stade, $vdt contient le code videotex à envoyer à l'utilisateur
    // On conserve l'objet dans le contexte utilisateur pour le récupérer lors de sa prochaine action
    $context['objDisplayPaginatedText'] = $objDisplayPaginatedText;
    // On ne change pas la valeur de $step car à la prochaine action on exécute de nouveau cette partie du script
    break 2;
?>




-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-




<?php


//Classe DisplayList


case 20:
    if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
        $step = 10; // Retour au sommaire
        break;
    }

    // Liste des éléments
    $list = array(
        0 => '1er choix',
        1 => '2ème choix',
        2 => '3ème choix',
        3 => '4ème choix'
    );

    // Récupération de l'éventuel objet existant dans le contexte utilisateur
    $objDisplayList = @$context['objDisplayList'];
    if (!($objDisplayList instanceof DisplayList)) {
        // L'utilisateur n'a pas l'objet dans son contexte : il vient d'arriver sur cette rubrique
        $vdtStart = MiniPavi\MiniPaviCli::clearScreen();
        $vdtStart .= file_get_contents('fond-de-page.vdt');

        // Effacement du texte affiché
        $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(3, 23);
        $vdtClearPage .= VDT_TXTBLACK . VDT_FDNORM . MiniPavi\MiniPaviCli::repeatChar(' ', 33);
        for ($i = 0; $i < 18; $i++) {
            $vdtClearPage .= MiniPavi\MiniPaviCli::setPos(1, 21 - $i);
            $vdtClearPage .= VDT_BGBLUE . MiniPavi\MiniPaviCli::repeatChar(' ', 33);
        }

        // Position du compteur de page
        $lCounter = 21;
        $cCounter = 35;

        // Compteur de page couleur Cyan
        $vdtPreCounter = VDT_TXTCYAN;

        // Position début du texte
        $lText = 5;
        $cText = 2;

        // On affiche rien de spécial avant chaque élément
        $vdtPreText = '';

        // Bas de page si ni Suite ni Retour acceptés (Sommaire n'est pas géré par l'objet, mais directement par le script)
        $vdtNone = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTBLACK . MiniPavi\MiniPaviCli::toG2("N°+ ") . VDT_FDINV . " Envoi " . VDT_FDNORM . " ou " . VDT_FDINV . " Sommaire ";

        // Bas de page si uniquement Suite accepté
        $vdtSuite = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTBLACK . MiniPavi\MiniPaviCli::toG2("N°+ ") . VDT_FDINV . " Envoi " . VDT_FDNORM . " " . VDT_FDINV . " Suite " . VDT_FDNORM . " ou " . VDT_FDINV . " Somm. ";

        // Bas de page si uniquement Retour accepté
        $vdtRetour = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTBLACK . MiniPavi\MiniPaviCli::toG2("N°+ ") . VDT_FDINV . " Envoi " . VDT_FDNORM . " " . VDT_FDINV . " Retour " . VDT_FDNORM . " ou " . VDT_FDINV . " Somm. ";

        // Bas de page si Suite et Retour acceptés
        $vdtSuiteRetour = MiniPavi\MiniPaviCli::setPos(3, 23) . VDT_TXTBLACK . MiniPavi\MiniPaviCli::toG2("N°+ ") . VDT_FDINV . " Envoi " . VDT_FDNORM . " " . VDT_FDINV . " Suite " . VDT_FDNORM . " " . VDT_FDINV . " Retour " . VDT_FDNORM;

        // Message d'erreur si première page atteinte et appui sur Retour
        $vdtErrNoPrev = MiniPavi\MiniPaviCli::toG2("Première page !");

        // Message d'erreur si dernière page atteinte et appui sur Suite
        $vdtErrNoNext = MiniPavi\MiniPaviCli::toG2("Dernière page !");

        // Message d'erreur si choix incorrect saisi
        $vdtErrChoice = MiniPavi\MiniPaviCli::toG2("Choix incorrect !");

        // 8 éléments maximum par page
        $lines = 8;

        // 1 ligne vide entre chaque élément
        $spaceLines = 1;

        // Le numéro de l'élément sera sur fond bleu, texte vert, inversé.
        // Le signe # représente le numéro de l'élément et est modifié à la volée
        $vdtItemNum = VDT_BGBLUE . VDT_TXTGREEN . VDT_FDINV . ' # ' . VDT_FDNORM . VDT_TXTYELLOW;

        // Initialisation
        $objDisplayList = new DisplayList(
            $vdtStart,
            $vdtClearPage,
            $list,
            $lCounter,
            $cCounter,
            $vdtPreCounter,
            $vdtItemNum,
            $lText,
            $cText,
            $vdtPreText,
            $vdtNone,
            $vdtSuite,
            $vdtRetour,
            $vdtSuiteRetour,
            $vdtErrNoPrev,
            $vdtErrNoNext,
            $vdtErrChoice,
            $lines,
            $spaceLines
        );

        // Exécution
        $r = $objDisplayList->process('', '', $vdt);
    } else {
        // L'utilisateur a déjà l'objet dans son contexte, exécution
        $r = $objDisplayList->process(MiniPavi\MiniPaviCli::$fctn, MiniPavi\MiniPaviCli::$content[0], $vdt);
    }

    // On conserve l'objet dans le contexte utilisateur pour le récupérer lors de sa prochaine action
    $context['objDisplayList'] = $objDisplayList;

    // Attente d'une saisie
    $cmd = MiniPavi\MiniPaviCli::createInputTxtCmd(34, 23, 2, MSK_ENVOI | MSK_SOMMAIRE | MSK_REPETITION | MSK_SUITE | MSK_RETOUR, true, ' ', '');

    if ($r == -1 || $r === false) {
        // L'utilisateur n'a pas saisi de choix (ou choix invalide)
        break 2;
    }

    // Un choix valide a été saisi, $r représente l’index du choix dans $list
    // On traite le choix saisi
    $vdt .= MiniPavi\MiniPaviCli::writeLine0('Choix = ' . $list[$r]);
    break 2;


?>