<?php
/**
 * @file MiniPwned.php
 * @author RenaudG
 * @version 1.0 Juin 2025
 *
 * Fonctions utilisées dans le script MiniPwned
 *
 */

function checkPasswordPwned($password) {
    $sha1 = strtoupper(sha1($password));
    $prefix = substr($sha1, 0, 5);
    $suffix = substr($sha1, 5);

    $url = "https://api.pwnedpasswords.com/range/" . $prefix;
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: MiniPavi-MiniPwned/1.0"
        ]
    ];
    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context); // @ pour éviter warning visible à l’utilisateur

    if ($result === false) {
        return -1; // -1 signifie erreur API
    }

    foreach (explode("\n", $result) as $line) {
        list($hashSuffix, $count) = explode(':', trim($line));
        if ($hashSuffix === $suffix) {
            return (int) $count;
        }
    }

    return 0;
}
?>