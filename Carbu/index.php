<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 0.1 Avril 2025
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