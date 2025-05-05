<?php
/*$resultats = getLotoResults();

if ($resultats) {
    echo "Tirage du " . $resultats['date'] . "\n";
    echo "Numéros : " . implode(", ", $resultats['numeros']) . "\n";
    echo "Numéro Chance : " . $resultats['chance'] . "\n";
    echo "Joker+ : " . ($resultats['joker'] ?? 'Non disponible') . "\n";
} else {
    echo "Erreur : impossible de récupérer les résultats du Loto.\n";
}*/
function getLotoResults(): ?array {
    $url = "https://www.fdj.fr/jeux/jeux-de-tirage/loto/resultats";
    $html = @file_get_contents($url);

    if (!$html) {
        return null;
    }

    // Expressions régulières pour extraire les données
    $getDate   = '#<h3 class="dateTirage[^"]*">(.+?)<\/h3>#si';
    $getNumbers = '#<p class="loto_boule">(.+?)<\/p>#si';
    $getChance  = '#<p class="loto_boule_c">(.+?)<\/p>#si';
    $getJoker   = '#<p class="tirage_joker_plus"[^>]*>(.+?)<\/p>#si';

    // Extraire les données
    preg_match_all($getDate, $html, $date);
    preg_match_all($getNumbers, $html, $numbers);
    preg_match_all($getChance, $html, $chance);
    preg_match_all($getJoker, $html, $joker);

    // Validation basique
    if (empty($date[1]) || count($numbers[1]) < 5 || empty($chance[1])) {
        return null;
    }

    return [
        'date'    => trim($date[1][0]),
        'numeros' => array_map('trim', array_slice($numbers[1], 0, 5)),
        'chance'  => trim($chance[1][0]),
        'joker'   => !empty($joker[1]) ? trim(strip_tags($joker[1][0])) : null
    ];
}


/*$resultats = getEuromillionsResults();

if ($resultats) {
    echo "Tirage du " . $resultats['date'] . "\n";
    echo "Numéros : " . implode(", ", $resultats['numeros']) . "\n";
    echo "Étoiles : " . implode(", ", $resultats['etoiles']) . "\n";
    echo "My Million : " . ($resultats['my_million'] ?? 'Non disponible') . "\n";
} else {
    echo "Erreur : Impossible de récupérer les résultats.\n";
}*/
function getEuromillionsResults(): ?array {
    $url = "https://www.fdj.fr/jeux/jeux-de-tirage/euromillions/resultats";
    $html = @file_get_contents($url);

    if (!$html) {
        return null;
    }

    // Regex patterns
    $getDate      = '#<h3 class="dateTirage[^"]*">(.+?)<\/h3>#si';
    $getNumbers   = '#<p class="euro_num">(.+?)<\/p>#si';
    $getChance    = '#<p class="euro_num_c">(.+?)<\/p>#si';
    $getMyMillion = '#<p class="tirage_my_million"><span>(.+?)<\/span><\/p>#si';

    // Match with regex
    preg_match_all($getDate, $html, $date);
    preg_match_all($getNumbers, $html, $numbers);
    preg_match_all($getChance, $html, $chances);
    preg_match_all($getMyMillion, $html, $myMillion);

    if (empty($date[1]) || count($numbers[1]) < 5 || count($chances[1]) < 2) {
        return null; // données manquantes
    }

    return [
        'date'       => trim($date[1][0]),
        'numeros'    => array_map('trim', array_slice($numbers[1], 0, 5)),
        'etoiles'    => array_map('trim', array_slice($chances[1], 0, 2)),
        'my_million' => !empty($myMillion[1]) ? trim($myMillion[1][0]) : null
    ];
}
?>