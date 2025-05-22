<?php
/**
 * @file MiniIss.php
 * @author RenaudG
 * @version 1.0 Mai 2025
 *
 * Fonctions utilisées dans le script MiniIss
 *
 */

$apiKey = 'VOTRE_CLE_API'; // Remplacez par votre clé API réelle

function getAstronauts() {
    $url = "http://api.open-notify.org/astros.json";
    $response = file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        return $data;
    } else {
        return ["message" => "Failed to retrieve data from : astros.json"];
    }
}

function getLocation() {
    $url = "http://api.open-notify.org/iss-now.json";
    $response = file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        return $data;
    } else {
        return ["message" => "Failed to retrieve data from : iss-now.json"];
    }
}

function convertDecimalToDMS($decimal, $isLatitude) {
    // Détermine si la valeur est positive ou négative
    $direction = $decimal < 0 ? -1 : 1;
    $decimal = abs($decimal);

    // Calcule les degrés
    $degrees = floor($decimal);

    // Calcule les minutes
    $temp = ($decimal - $degrees) * 60;
    $minutes = floor($temp);

    // Calcule les secondes
    $seconds = round(($temp - $minutes) * 60, 2);

    // Détermine le cardinal
    if ($isLatitude) {
        $cardinal = $direction == 1 ? 'nord' : 'sud';
    } else {
        $cardinal = $direction == 1 ? 'est' : 'ouest';
    }

    // Retourne le résultat sous forme de chaîne formatée
    return sprintf("%d° %d' %.0f\" %s", $degrees, $minutes, $seconds, $cardinal);
}

function getPosition($latitude, $longitude) {
    global $apiKey;

    $url = 'https://api.mistral.ai/v1/chat/completions';

    $prompt = "Détermine la position de l'ISS via ces coordonnées :\nlongitude: $longitude\nlatitude: $latitude\n\nDonne une réponse commençant par : \"L'ISS se trouve actuellement...\"";

    $data = [
        'model' => 'mistral-small-latest',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.2
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return 'Erreur cURL: ' . curl_error($ch);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        } else {
            return 'Format de réponse inattendu';
        }
    } else {
        return 'Erreur HTTP: ' . $httpCode;
    }
}

?>