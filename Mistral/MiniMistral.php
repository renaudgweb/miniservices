<?php
/**
 * @file MiniMistral.php
 * @author RenaudG
 * @version 0.2 Avril 2025
 *
 * Fonctions utlisées dans le script MiniMistral
 * 
 */

$apiKey = 'VOTRE_CLE_API'; // Remplacez par votre clé API réelle

function getMistralResponse($userPrompt) {
    global $apiKey;

    $url = 'https://api.mistral.ai/v1/chat/completions';

    // Message système définissant le contexte
    $systemMessage = "Vous êtes un Minitel intelligent. Nous sommes dans les années 80. Vous fournissez des informations comme le ferait un Minitel, en utilisant un ton adapté à cette époque. Vous avez accès à une base de données étendue et pouvez répondre à une variété de questions. Utilisez un langage clair et concis, avec un maximum de 600 caractères.";

    $data = [
        'model' => 'mistral-large-latest',
        'messages' => [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.8,
        'max_tokens' => 1024
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
        // Assuming the response contains a 'choices' array with 'message' and 'content'
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
