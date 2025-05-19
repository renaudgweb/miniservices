<?php
/**
 * @file MiniCarbu.php
 * @author RenaudG
 * @version 1.0 Mai 2025
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

    if (curl_errno($ch)) {
        throw new Exception('Error:' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Afficher la réponse brute pour le débogage
    error_log("Raw response from Prix Carburants API: " . $response);
    error_log("HTTP Status Code: " . $httpCode);

    if ($httpCode !== 200 && $httpCode !== 206) {
        throw new Exception("Failed to retrieve data from Prix Carburants API. HTTP Status Code: " . $httpCode);
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON response from Prix Carburants API. Error: " . json_last_error_msg());
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

    if (curl_errno($ch)) {
        throw new Exception('Error:' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Afficher la réponse brute pour le débogage
    error_log("Raw response from Prix Carburants API for station details: " . $response);
    error_log("HTTP Status Code: " . $httpCode);

    if ($httpCode !== 200 && $httpCode !== 206) {
        if ($httpCode === 429) {
            error_log("Rate limit exceeded. Retrying after 10 seconds.");
            sleep(10); // Attendre 10 secondes avant de réessayer
            return getStationDetails($stationId); // Réessayer
        }
        throw new Exception("Failed to retrieve station details from Prix Carburants API. HTTP Status Code: " . $httpCode);
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON response from Prix Carburants API. Error: " . json_last_error_msg());
    }

    if (is_array($data) || (is_object($data) && $data = (array) $data)) {
        return $data;
    } else {
        throw new Exception("Failed to retrieve station details or invalid response format.");
    }
}

function writeToFile($filename, $content) {
    file_put_contents($filename, $content, FILE_APPEND);
}

function displayFuelPrices($stations, $filename) {
    // Efface le contenu du fichier s'il existe déjà
    file_put_contents($filename, '');

    if (!is_array($stations)) {
        throw new Exception("Invalid stations data.");
    }

    foreach ($stations as $station) {
        $stationId = $station['id'];
        $stationDetails = getStationDetails($stationId);

        $name = $stationDetails['name'];
        $address = $stationDetails['Address']['street_line'] . ",\n" . $stationDetails['Address']['city_line'];
        $distance = $station['distance'] / 1000;
        $distance = round($distance, 1);

        $content = $name . " (" . $distance . " km)\n";
        $content .= "$address\n";

        if (isset($stationDetails['Fuels']) && is_array($stationDetails['Fuels'])) {
            foreach ($stationDetails['Fuels'] as $fuel) {
                $fuelName = $fuel['short_name'];
                $fuelPriceInEuros = $fuel['Price']['value'];

                // Convertir le prix en francs
                $fuelPriceInFrancs = round($fuelPriceInEuros * 6.55957, 2);
                $content .= " - $fuelName: " . $fuelPriceInFrancs . "F (" . $fuelPriceInEuros . "EUR)\n";
            }
        } else {
            echo "No fuel prices available.\n";
        }

        $content .= "\n--------------------------------------\n";

        writeToFile($filename, "\n" . $content);
    }
}
?>
