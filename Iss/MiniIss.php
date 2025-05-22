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

// Exemple d'utilisation
/*$astronauts = getAstronauts();
$location = getLocation();
$position = getPosition($location['iss_position']['latitude'], $location['iss_position']['longitude']);

print_r($astronauts);
print_r($location);
echo $position;*/

?>
