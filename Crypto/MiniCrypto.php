<?php
/**
 * @file MiniCrypto.php
 * @author RenaudG
 * @version 1.1 Avril 2025
 *
 * Fonctions utlisées dans le script MiniCrypto
 * 
 */

$URL = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin%2Cethereum%2Cripple%2Cbinancecoin%2Csolana%2Ctron%2Cdogecoin%2Ccardano%2C&vs_currencies=eur';

function getPrices() {
    global $URL;
    $tRes = array();

    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Si on reçoit une erreur 429, attendre 1 minute et réessayer une fois
    if ($httpCode === 429) {
        sleep(60);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }

    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        // Si la récupération échoue ou n'est pas OK, retourner tableau vide
        return $tRes;
    }

    // Décoder le JSON
    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return $tRes;
    }

    // Tableau avec l'ordre souhaité des cryptomonnaies
    $order = array('bitcoin', 'ethereum', 'ripple', 'binancecoin', 'solana', 'dogecoin', 'tron', 'cardano');

    foreach ($order as $crypto) {
        if (isset($data[$crypto])) {
            $tRes[] = array(
                'titre' => ucfirst($crypto), // Mettre la première lettre en majuscule
                'desc' => round($data[$crypto]['eur'] * 6.55957, 2) . "F (" . $data[$crypto]['eur'] . "EUR)"
            );
        }
    }

    return $tRes;
}
?>