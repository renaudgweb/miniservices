<?php
/**
 * @file MiniCarbu.php
 * @author RenaudG
 * @version 0.1 Avril 2025
 *
 * Fonctions utlisées dans le script MiniCarbu
 * 
 */

function getCoordinatesFromOpenMeteo($location) {
    $apiUrl = "https://geocoding-api.open-meteo.com/v1/search?name={$location}&count=1&language=fr&format=json&countryCode=FR";

    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);

    if (isset($data['results'][0])) {
        $latitude = $data['results'][0]['latitude'];
        $longitude = $data['results'][0]['longitude'];
        return [$latitude, $longitude];
    } else {
        throw new Exception("Unable to retrieve coordinates for the given location.");
    }
}

function getNearbyStations($latitude, $longitude) {
    $apiUrl = "https://api.prix-carburants.2aaz.fr/stations/around/{$latitude},{$longitude}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('Error:' . curl_error($ch));
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data;
}

function getStationDetails($stationId) {
    $apiUrl = "https://api.prix-carburants.2aaz.fr/station/{$stationId}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('Error:' . curl_error($ch));
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data;
}

function displayFuelPrices($stations) {
    foreach ($stations as $station) {
        $stationId = $station['id'];
        $stationDetails = getStationDetails($stationId);

        $name = $stationDetails['name'];
        $address = $stationDetails['Address']['street_line'] . ", " . $stationDetails['Address']['city_line'];

        echo "Station: $name\n";
        echo "Address: $address\n";
        echo "Fuel Prices:\n";

        foreach ($stationDetails['Fuels'] as $fuel) {
            $fuelName = $fuel['name'];
            $fuelPrice = $fuel['Price']['text'];
            echo " - $fuelName: $fuelPrice\n";
        }

        echo "\n";
    }
}
?>