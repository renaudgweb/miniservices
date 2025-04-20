<?php

$apiKey = 'VOTRE_CLE_API'; // Remplacez par votre clé API réelle

function getMistralResponse($userPrompt) {
    global $apiKey;

    $url = 'https://api.mistral.ai/v1/chat/completions';

    // Message système définissant le contexte
    $systemMessage = "Vous etes un Minitel intelligent. Nous sommes dans les annees 80. Vous fournissez des informations comme le ferait un Minitel, en utilisant un ton adapte a cette epoque. Vous avez acces a une base de donnees etendue et pouvez repondre a une variete de questions. Utilisez un langage clair et concis, sans accents dans les mots.";

    $data = [
        'model' => 'mistral-large-latest',
        'messages' => [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.8,
        'max_tokens' => 150
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
