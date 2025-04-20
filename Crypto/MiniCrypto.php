<?php
/**
 * @file MiniCrypto.php
 * @author RenaudG
 * @version 0.4 Avril 2025
 *
 * Fonctions utlisées dans le script MiniCrypto
 * 
 */

$URL = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin%2Cethereum%2Cripple%2Cbinancecoin%2Csolana%2Ctron%2Cdogecoin%2Ccardano%2C&vs_currencies=usd%2Ceur';

function getPrices() {
    global $URL;
    $tRes = array();

    // Récupérer les données JSON depuis l'URL
    $jsonData = file_get_contents($URL);

    if ($jsonData === FALSE) {
        // Si la récupération des données échoue, retourner un tableau vide
        return $tRes;
    }

    // Décoder le JSON
    $data = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // Si le JSON est invalide, retourner un tableau vide
        return $tRes;
    }

    // Tableau avec l'ordre souhaité des cryptomonnaies
    $order = array('bitcoin', 'ethereum', 'ripple', 'binancecoin', 'solana', 'dogecoin', 'tron', 'cardano');

    foreach ($order as $crypto) {
        if (isset($data[$crypto])) {
            $tRes[] = array(
                'titre' => ucfirst($crypto), // Mettre la première lettre en majuscule
                'desc' => $data[$crypto]['eur'] . " EUR (" . $data[$crypto]['usd'] . " \$US)",
            );
        }
    }

    return $tRes;
}
?>