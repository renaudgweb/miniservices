<?php
/**
 * @file MiniLoto.php
 * @author RenaudG
 * @version 1.0 Mai 2025
 *
 * Fonctions utlisées dans le script MiniLoto
 *
 */

function getLotoResultat() {
    $url = 'https://tirage-gagnant.com/loto/resultats-loto/';

    // Charger le HTML de la page
    $html = file_get_contents($url);
    if (!$html) return null;

    // Initialiser DOM
    libxml_use_internal_errors(true); // Ignorer les warnings HTML mal formé
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Extraire la date (format complet)
    $dateNode = $xpath->query('//div[@id="resultat_date"]//span[@class="date_full"]')->item(0);
    $date = $dateNode ? trim($dateNode->textContent) : null;

    // Extraire le jackpot
    $jackpotNode = $xpath->query('//div[@id="resultat_date"]//p[@class="jackpot"]')->item(0);
    $jackpot = $jackpotNode ? trim($jackpotNode->textContent) : null;

    // Extraire les numéros
    $numNodes = $xpath->query('//div[@id="resultat_date"]//div[@class="resultat"]/p[@class="num"]');
    $numeros = [];
    foreach ($numNodes as $node) {
        $numeros[] = trim($node->textContent);
    }

    // Extraire le numéro chance
    $chanceNode = $xpath->query('//div[@id="resultat_date"]//div[@class="resultat"]/p[@class="chance"]')->item(0);
    $chance = $chanceNode ? trim($chanceNode->textContent) : null;

    return [
        'date' => $date,
        'jackpot' => $jackpot,
        'numeros' => $numeros,
        'chance' => $chance
    ];
}

function getEuromillionsResultat() {
    $url = 'https://tirage-gagnant.com/euromillions/resultats-euromillions/';

    // Charger le HTML de la page
    $html = file_get_contents($url);
    if (!$html) return null;

    // Initialiser DOM
    libxml_use_internal_errors(true); // Ignorer les warnings HTML mal formé
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Extraire la date (format complet)
    $dateNode = $xpath->query('//div[@id="resultat_date"]//span[@class="date_full"]')->item(0);
    $date = $dateNode ? trim($dateNode->textContent) : null;

    // Extraire le jackpot
    $jackpotNode = $xpath->query('//div[@id="resultat_date"]//p[@class="jackpot"]')->item(0);
    $jackpot = $jackpotNode ? trim($jackpotNode->textContent) : null;

    // Extraire les numéros
    $numNodes = $xpath->query('//div[@id="resultat_date"]//div[@class="resultat"]/p[@class="num_v2"]');
    $numeros = [];
    foreach ($numNodes as $node) {
        $numeros[] = trim($node->textContent);
    }

    // Extraire le numéro chance
    $chanceNodes = $xpath->query('//div[@id="resultat_date"]//div[@class="resultat"]/p[@class="etoile_v2"]');
    $chances = [];
    foreach ($chanceNodes as $node) {
        $chances[] = trim($node->textContent);
    }

    return [
        'date' => $date,
        'jackpot' => $jackpot,
        'numeros' => $numeros,
        'chances' => $chances
    ];
}
?>