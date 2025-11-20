<?php
/**
 * @file MiniMistral.php
 * @author RenaudG
 * @version 1.3 Novembre 2025
 *
 * Fonctions utlisées dans le script MiniMistral
 *
 */

$apiKey = 'VOTRE_CLE_API'; // Remplacez par votre clé API réelle

function getMistralResponse($userPrompt) {
    global $apiKey;

    // --- Préparation du tableau de retour ---
    $lines = [];
    $lines[] = "MISTRAL IA"; 

    $url = 'https://api.mistral.ai/v1/chat/completions';

    // Caractères spéciaux pour le prompt système
    $tabAcc = array('é', 'è', 'à', 'ç', 'ê', 'É', 'È', 'À', 'Ç', 'Ê', 'β', 'ß', 'œ', 'Œ', 'ü', 'û', 'ú', 'ù', 'ö', 'ô', 'ó', 'ò', 'ï', 'î', 'í', 'ì', 'ë', 'ä', 'â', 'á', '£', '°', '±', '←', '↑', '→', '↓', '¼', '½', '¾', 'Â', 'Î', 'ō', 'á', '’', ' ', 'ň', 'ć', 'ř', 'ý', 'š', 'í', 'ą');

    $systemMessage = "Vous êtes un Minitel intelligent. Nous sommes dans les années 80.
    Vous devez toujours terminer vos réponses de manière complète et cohérente.
    Utilisez uniquement les caractères spéciaux suivants si nécessaire : " . implode(', ', $tabAcc) . ".
    Assurez-vous que vos réponses soient concises, claires et adaptées au style rétro de l'époque.";

    // Configuration initiale (Modèle Principal)
    $modelPrincipal = 'mistral-medium-latest';
    $modelFallback = 'mistral-small-latest'; // ou 'open-mistral-7b' pour encore plus léger

    $data = [
        'model' => $modelPrincipal,
        'messages' => [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.8,
        'max_tokens' => 2048
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    // --- TENTATIVE 1 : Modèle Principal ---
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Gestion erreur technique (pas internet etc)
    if (curl_errno($ch)) {
        $lines[] = "Erreur connexion (1).";
        curl_close($ch);
        return $lines;
    }
    curl_close($ch);


    // --- TENTATIVE 2 : Fallback si 429 (Trop de requêtes) ---
    if ($httpCode == 429) {
        // On log l'événement
        error_log("Mistral 429 sur $modelPrincipal. Bascule vers $modelFallback.");

        // On change le modèle dans les données
        $data['model'] = $modelFallback;

        // On relance cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // On ré-encode avec le nouveau modèle
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }


    // --- TRAITEMENT DE LA RÉPONSE (Commune aux tentatives) ---
    if ($httpCode == 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData['choices'][0]['message']['content'])) {
            
            $content = $responseData['choices'][0]['message']['content'];
            
            // Info dans le log pour savoir quel modèle a répondu
            $usedModel = ($data['model'] == $modelPrincipal) ? "MEDIUM" : "SMALL (Fallback)";

            // Log
            file_put_contents(
                'mistral.log',
                date('d/m/Y H:i:s') . " - USER :\n" . $userPrompt . "\n\nMISTRAL ($usedModel) :\n" . $content . "\n\n---------------\n\n",
                FILE_APPEND
            );

            // Préparation affichage
            $contentLines = explode("\n", $content);
            $lines = array_merge($lines, $contentLines);

        } else {
            $lines[] = 'Réponse vide.';
        }
    } elseif ($httpCode == 429) {
        // Si même le modèle Small répond 429
        $lines[] = "Reseau sature.";
        $lines[] = "Meme le mode 'Eco'";
        $lines[] = "ne repond pas.";
        $lines[] = "Reessayez plus tard.";
    } else {
        $lines[] = "Erreur API Mistral.";
        $lines[] = "Code HTTP: " . $httpCode;
        $lines[] = "Modele: " . $data['model'];
    }

    return $lines;
}
?>