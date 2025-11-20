<?php
/**
 * @file MiniMistral.php
 * @author RenaudG
 * @version 1.2 Novembre 2025
 *
 * Fonctions utlisées dans le script MiniMistral
 *
 */

$apiKey = 'VOTRE_CLE_API'; // Remplacez par votre clé API réelle

function getMistralResponse($userPrompt) {
    global $apiKey;

    // --- Préparation du tableau de retour (Ligne 0 = Titre) ---
    $lines = [];
    $lines[] = "3614 MISTRAL"; // Titre obligatoire pour DisplayPaginatedText

    $url = 'https://api.mistral.ai/v1/chat/completions';

    // Liste des caractères spéciaux autorisés (pour info au système)
    $tabAcc = array('é', 'è', 'à', 'ç', 'ê', 'É', 'È', 'À', 'Ç', 'Ê',
        'β', 'ß', 'œ', 'Œ', 'ü', 'û', 'ú', 'ù', 'ö', 'ô', 'ó', 'ò', 'ï', 'î', 'í', 'ì', 'ë', 'ä',
        'â', 'á', '£', '°', '±', '←', '↑', '→', '↓', '¼', '½', '¾', 'Â', 'Î', 'ō', 'á', '’', ' ', 'ň', 'ć', 'ř', 'ý', 'š', 'í', 'ą');

    $systemMessage = "Vous êtes un Minitel intelligent. Nous sommes dans les années 80.
    Vous devez toujours terminer vos réponses de manière complète et cohérente.
    Utilisez uniquement les caractères spéciaux suivants si nécessaire : " . implode(', ', $tabAcc) . ".
    Assurez-vous que vos réponses soient concises, claires et adaptées au style rétro de l'époque.";

    $data = [
        'model' => 'mistral-medium-latest',
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

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    // Timeout de sécurité pour éviter que le Minitel ne fige trop longtemps
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); 

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $lines[] = "Erreur de connexion.";
        $lines[] = "Details : " . curl_error($ch);
        return $lines;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData['choices'][0]['message']['content'])) {
            
            $content = $responseData['choices'][0]['message']['content'];
            
            // --- 1. LOGGING (On garde le fichier de log) ---
            // On écrit dans le fichier .log pour l'historique (append)
            file_put_contents(
                'mistral.log',
                date('d/m/Y H:i:s') . " - USER :\n" . $userPrompt . "\n\nMISTRAL :\n" . $content . "\n\n---------------\n\n",
                FILE_APPEND
            );

            // --- 2. PREPARATION AFFICHAGE (Plus de fichier .txt) ---
            // On découpe la réponse par ligne pour l'affichage
            // (DisplayPaginatedText gère mieux les tableaux de lignes)
            $contentLines = explode("\n", $content);
            
            // On fusionne avec notre tableau (qui contient déjà le titre)
            $lines = array_merge($lines, $contentLines);

        } else {
            $lines[] = 'Réponse vide ou format inattendu.';
        }
    } else {
        $lines[] = "Erreur API Mistral.";
        $lines[] = "Code HTTP: " . $httpCode;
    }

    return $lines;
}
?>