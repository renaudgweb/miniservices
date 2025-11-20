<?php
/**
 * @file MiniMistral.php
 * @author RenaudG
 * @version 1.4 Novembre 2025
 *
 * Fonctions utlisées dans le script MiniMistral
 *
 */

$apiKey = 'VOTRE_CLE_API'; // Remplacez par votre clé API réelle

/**
 * Fonction de gestion des logs avec rotation automatique
 */
function writeMistralLog($message) {
    $logFile = 'mistral.log';
    $maxSize = 2 * 1024 * 1024; // 2 Mo

    // Rotation si nécessaire
    if (file_exists($logFile) && filesize($logFile) > $maxSize) {
        // On archive l'ancien log : mistral_20250520-140000.log
        $archiveName = 'mistral_' . date('Ymd-His') . '.log';
        rename($logFile, $archiveName);
    }

    // Écriture
    file_put_contents($logFile, $message, FILE_APPEND);
}

function getMistralResponse($userPrompt) {
    global $apiKey;

    $lines = [];
    $lines[] = "MISTRAL IA"; 

    $url = 'https://api.mistral.ai/v1/chat/completions';

    $tabAcc = array('é', 'è', 'à', 'ç', 'ê', 'É', 'È', 'À', 'Ç', 'Ê', 'β', 'ß', 'œ', 'Œ', 'ü', 'û', 'ú', 'ù', 'ö', 'ô', 'ó', 'ò', 'ï', 'î', 'í', 'ì', 'ë', 'ä', 'â', 'á', '£', '°', '±', '←', '↑', '→', '↓', '¼', '½', '¾', 'Â', 'Î', 'ō', 'á', '’', ' ', 'ň', 'ć', 'ř', 'ý', 'š', 'í', 'ą');

    $systemMessage = "Vous êtes un Minitel intelligent. Nous sommes dans les années 80.
    Vous devez toujours terminer vos réponses de manière complète et cohérente.
    Utilisez uniquement les caractères spéciaux suivants si nécessaire : " . implode(', ', $tabAcc) . ".
    Assurez-vous que vos réponses soient concises, claires et adaptées au style rétro de l'époque.";

    $modelPrincipal = 'mistral-medium-latest';
    $modelFallback = 'mistral-small-latest'; 

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

    // --- TENTATIVE 1 ---
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $lines[] = "Erreur connexion.";
        curl_close($ch);
        return $lines;
    }
    curl_close($ch);

    // --- TENTATIVE 2 (Fallback) ---
    if ($httpCode == 429) {
        error_log("Mistral 429 sur $modelPrincipal. Bascule vers $modelFallback.");
        $data['model'] = $modelFallback;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // --- TRAITEMENT ---
    if ($httpCode == 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData['choices'][0]['message']['content'])) {
            
            $content = $responseData['choices'][0]['message']['content'];
            $usedModel = ($data['model'] == $modelPrincipal) ? "mistral-medium-latest" : "mistral-small-latest";

            // --- UTILISATION DE LA NOUVELLE FONCTION DE LOG ---
            $logMessage = date('d/m/Y H:i:s') . " - USER :\n" . $userPrompt . "\n\n($usedModel) - MISTRAL :\n" . $content . "\n\n---------------\n\n";
            writeMistralLog($logMessage);
            // --------------------------------------------------

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