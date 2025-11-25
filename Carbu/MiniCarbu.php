<?php
/**
 * @file MiniCarbu.php
 * @author RenaudG
 * @version 1.2 Novembre 2025
 *
 * Fonctions utilisées dans le script MiniCarbu
 *
 */

function getCoordinatesFromOpenMeteo($location) {
    $apiUrl = "https://geocoding-api.open-meteo.com/v1/search?name={$location}&count=1&language=fr&format=json&countryCode=FR";

    $response = file_get_contents($apiUrl);
    if ($response === FALSE) {
        throw new Exception("Failed to retrieve data from OpenMeteo API.");
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON response from OpenMeteo API.");
    }

    $latitude = $data['results'][0]['latitude'];
    $longitude = $data['results'][0]['longitude'];
    return [$latitude, $longitude];

}

function getNearbyStations($latitude, $longitude) {
    $apiUrl = "https://api.prix-carburants.2aaz.fr/stations/around/{$latitude},{$longitude}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        'Content-Type: application/json',
        'Range: station=1-6'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    // Vérifier si l'erreur est un problème SSL (code erreur 60)
    if (curl_errno($ch) == 60) {
        error_log("Certificat expiré détecté. Nouvelle tentative sans vérification SSL.");

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
    }

    if (curl_errno($ch)) {
        throw new Exception('Error:' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Raw response from Prix Carburants API: " . $response);
    error_log("HTTP Status Code: " . $httpCode);

    if ($httpCode !== 200 && $httpCode !== 206) {
        throw new Exception("Failed to retrieve data from Prix Carburants API. HTTP Status Code: " . $httpCode);
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON. Error: " . json_last_error_msg());
    }

    return $data;
}


function getStationDetails($stationId) {
    $apiUrl = "https://api.prix-carburants.2aaz.fr/station/{$stationId}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        'Content-Type: application/json',
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    // Détection du certificat expiré
    if (curl_errno($ch) == 60) {
        error_log("Certificat expiré détecté pour station $stationId. Tentative sans SSL.");

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
    }

    if (curl_errno($ch)) {
        throw new Exception('Error:' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Raw response from Prix Carburants API for station details: " . $response);
    error_log("HTTP Status Code: " . $httpCode);

    if ($httpCode !== 200 && $httpCode !== 206) {
        if ($httpCode === 429) {
            error_log("Rate limit exceeded. Retrying after 10 seconds.");
            sleep(10);
            return getStationDetails($stationId);
        }
        throw new Exception("Failed to retrieve station details. HTTP Status Code: " . $httpCode);
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg());
    }

    if (is_array($data) || (is_object($data) && $data = (array) $data)) {
        return $data;
    } else {
        throw new Exception("Invalid response format.");
    }
}

/**
 * Retourne un tableau de lignes formatées pour DisplayPaginatedText
 * au lieu d'écrire dans un fichier.
 */
function getStationsAsArray($stations) {
    $lines = [];

    // LIGNE 0 : LE TITRE (Obligatoire pour votre classe d'affichage)
    $lines[] = "3613 CARBU"; 

    // CAS VIDE : Si le tableau est vide ou n'est pas un tableau
    if (empty($stations) || !is_array($stations)) {
        // On ajoute des lignes explicatives
        $lines[] = ""; // Une ligne vide pour aérer
        $lines[] = "Aucune station trouvée dans ce";
        $lines[] = "secteur géographique.";
        $lines[] = "";
        $lines[] = "Conseils :";
        $lines[] = "- Verifiez l'orthographe";
        $lines[] = "- Essayez une ville voisine";
        $lines[] = "- Elargissez la zone";
        
        return $lines; // On s'arrête là et on renvoie le message
    }

    // CAS NORMAL : On boucle sur les stations
    foreach ($stations as $station) {
        $stationId = $station['id'];
        
        try {
            $stationDetails = getStationDetails($stationId);
        } catch (Exception $e) {
            continue; 
        }

        $name = $stationDetails['name'];
        // S'assurer que l'adresse existe pour éviter des erreurs
        $street = isset($stationDetails['Address']['street_line']) ? $stationDetails['Address']['street_line'] : '';
        $city = isset($stationDetails['Address']['city_line']) ? $stationDetails['Address']['city_line'] : '';
        
        $distance = round($station['distance'] / 1000, 1);

        $block = "$name ($distance km)\n";
        $block .= "$street, $city\n";

        if (isset($stationDetails['Fuels']) && is_array($stationDetails['Fuels'])) {
            foreach ($stationDetails['Fuels'] as $fuel) {
                $fuelName = $fuel['shortName'];
                $fuelPriceInEuros = $fuel['Price']['value'];
                $fuelPriceInFrancs = round($fuelPriceInEuros * 6.55957, 2);
                
                $block .= " - $fuelName: " . $fuelPriceInFrancs . "F (" . $fuelPriceInEuros . "EUR)\n";
            }
        } else {
            $block .= "Prix non disponibles.\n";
        }
        
        $block .= "\n``````````````````````````````````````\n";

        $lines[] = $block;
    }

    return $lines;
}
?>
