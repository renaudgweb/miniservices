<?php
/**
 * @file livre.php
 * @author RenaudG
 * @version 1.0 Juillet 2026
 *
 * Livre d'or : recensement des interactions des utilisateurs
 * avec le service Minitel MiniMistral (3614 MISTRAL).
 *
 * Lit mistral.log (+ archives mistral_*.log) et affiche chaque
 * echange USER / MISTRAL dans un style Minitel (fond noir, texte clair).
 *
 * Aucune base de donnees : parsing direct des fichiers log.
 */

// --- CONFIGURATION -----------------------------------------------------------
date_default_timezone_set('Europe/Paris'); // affichage coherent des dates

$logFile   = 'mistral.log';
$separator = '---------------';
$perPage   = 50;    // Nombre d'echanges par page

// --- LECTURE DES LOGS --------------------------------------------------------
// On lit le log courant + toutes les archives mistral_YYYYMMDD-HHMMSS.log
$files = array();
if (file_exists($logFile)) {
    $files[] = $logFile;
}
foreach (glob('mistral_*.log') as $archive) {
    $files[] = $archive;
}

// --- PARSING ----------------------------------------------------------------
// Deux formats de log coexistent (parseur double) :
//   JSONL  (actuel) : un objet JSON par ligne
//   Texte  (ancien) : blocs "<date> - USER :\n...\n(<model>) - MISTRAL :\n..."
//                     separes par "---------------"
$entries = array();

// Parse un bloc texte legacy et renvoie une entree, ou null si non reconnu.
function parseLegacyBlock($block) {
    $block = trim($block);
    if ($block === '') {
        return null;
    }
    $date = $model = $user = $mistral = '';

    // Le prefixe "(<model>) - " est optionnel (les tout premiers logs
    // n'avaient pas le modele), mais "MISTRAL :" separe question/reponse.
    if (preg_match('/^(.*?)\s*-\s*USER\s*:\s*\n(.*?)\n\s*\n(?:\(([^)]*)\)\s*-\s*)?MISTRAL\s*:\s*\n?(.*)$/s', $block, $m)) {
        $date    = trim($m[1]);
        $user    = trim($m[2]);
        $model   = isset($m[3]) ? trim($m[3]) : '';
        $mistral = isset($m[4]) ? trim($m[4]) : '';
    } elseif (preg_match('/^(.*?)\s*-\s*USER\s*:\s*\n(.*)$/s', $block, $m)) {
        // Reponse absente / non loggee : on affiche au moins la question
        $date = trim($m[1]);
        $user = trim($m[2]);
    } else {
        return null; // bloc non reconnu
    }

    return array(
        'date'        => $date,
        'model'       => $model,
        'user'        => $user,
        'mistral'     => $mistral,
        'tokens'      => null,
        'duration_ms' => null,
        'session'     => '',
    );
}

foreach ($files as $f) {
    $content = @file_get_contents($f);
    if ($content === false || trim($content) === '') {
        continue;
    }

    // Les deux formats peuvent COHABITER dans un meme fichier :
    // l'ancien log texte est reste, les nouveaux echanges s'ajoutent en JSONL.
    // On parcourt donc ligne par ligne : ligne "{...}" = JSONL,
    // le reste est accumule puis parse comme bloc(s) texte legacy.
    $legacyBuffer = '';

    foreach (explode("\n", $content) as $line) {
        $trimmed = ltrim($line);

        if ($trimmed !== '' && $trimmed[0] === '{') {
            $obj = json_decode(trim($line), true);
            if (is_array($obj) && isset($obj['user'])) {
                // On vide d'abord le buffer legacy accumule avant cette ligne
                foreach (explode($separator, $legacyBuffer) as $block) {
                    $en = parseLegacyBlock($block);
                    if ($en !== null) {
                        $entries[] = $en;
                    }
                }
                $legacyBuffer = '';

                $entries[] = array(
                    'date'        => isset($obj['date'])        ? (string) $obj['date']    : '',
                    'model'       => isset($obj['model'])       ? (string) $obj['model']   : '',
                    'user'        => isset($obj['user'])        ? (string) $obj['user']    : '',
                    'mistral'     => isset($obj['mistral'])     ? (string) $obj['mistral'] : '',
                    'tokens'      => isset($obj['tokens'])      ? $obj['tokens']           : null,
                    'duration_ms' => isset($obj['duration_ms']) ? $obj['duration_ms']      : null,
                    'session'     => isset($obj['session'])     ? (string) $obj['session'] : '',
                );
                continue;
            }
        }

        // Ligne texte legacy : on accumule
        $legacyBuffer .= $line . "\n";
    }

    // Reste du buffer legacy en fin de fichier
    foreach (explode($separator, $legacyBuffer) as $block) {
        $en = parseLegacyBlock($block);
        if ($en !== null) {
            $entries[] = $en;
        }
    }
}

// Normalise chaque date en timestamp pour un tri fiable
// (JSONL = ISO 8601 "2026-07-26T14:30:00+02:00" ; legacy = "26/07/2026 14:30:00")
function mistralTs($date) {
    if ($date === '') {
        return 0;
    }
    // Format legacy jj/mm/aaaa hh:mm:ss
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})\s+(\d{2}):(\d{2}):(\d{2})#', $date, $d)) {
        return mktime((int)$d[4], (int)$d[5], (int)$d[6], (int)$d[2], (int)$d[1], (int)$d[3]);
    }
    $ts = strtotime($date);
    return $ts !== false ? $ts : 0;
}
foreach ($entries as &$en) {
    $en['ts'] = mistralTs($en['date']);
}
unset($en);

// Plus recent en premier (tri stable sur le timestamp)
usort($entries, function ($a, $b) {
    return $b['ts'] <=> $a['ts'];
});
$total = count($entries);

// --- PAGINATION -------------------------------------------------------------
$totalPages = max(1, (int) ceil($total / $perPage));
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
if ($page < 1)          { $page = 1; }
if ($page > $totalPages) { $page = $totalPages; }

$offset  = ($page - 1) * $perPage;
$entries = array_slice($entries, $offset, $perPage);

function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>3614 MISTRAL - Livre d'or</title>
<style>
    :root {
        --bg:      #000000;
        --fg:      #d7d7d7;  /* blanc laiteux facon Minitel */
        --dim:     #808080;
        --accent:  #ffffff;
        --user:    #a8d0ff;  /* leger bleuté pour l'utilisateur */
        --line:    #303030;
    }
    * { box-sizing: border-box; }
    html, body {
        margin: 0;
        padding: 0;
        background: var(--bg);
        color: var(--fg);
    }
    body {
        font-family: "Courier New", "DejaVu Sans Mono", monospace;
        font-size: 15px;
        line-height: 1.5;
        padding: 20px 12px 60px;
        max-width: 760px;
        margin: 0 auto;
        /* Legere lueur phosphore */
        text-shadow: 0 0 1px rgba(215,215,215,0.4);
    }
    /* Effet scanlines discret */
    body::before {
        content: "";
        position: fixed;
        inset: 0;
        pointer-events: none;
        background: repeating-linear-gradient(
            to bottom,
            rgba(0,0,0,0) 0px,
            rgba(0,0,0,0) 2px,
            rgba(0,0,0,0.25) 3px
        );
        z-index: 9999;
    }
    header {
        text-align: center;
        border: 1px solid var(--line);
        padding: 14px;
        margin-bottom: 24px;
    }
    header h1 {
        margin: 0 0 6px;
        font-size: 22px;
        letter-spacing: 3px;
        color: var(--accent);
    }
    header .sub {
        color: var(--dim);
        font-size: 13px;
        letter-spacing: 1px;
    }
    .count {
        color: var(--dim);
        font-size: 12px;
        text-align: center;
        margin-bottom: 24px;
    }
    .entry {
        border-top: 1px solid var(--line);
        padding: 16px 0;
    }
    .meta {
        color: var(--dim);
        font-size: 12px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 4px;
    }
    .meta .model { text-transform: uppercase; letter-spacing: 1px; text-align: right; }
    .meta .sess { color: var(--line); }
    .q, .a {
        white-space: pre-wrap;
        word-wrap: break-word;
        margin: 6px 0;
    }
    .label {
        color: var(--dim);
        font-size: 12px;
        letter-spacing: 1px;
    }
    .q { color: var(--user); }
    .a { color: var(--fg); }
    .empty {
        text-align: center;
        color: var(--dim);
        padding: 60px 0;
    }
    .pager {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--line);
        margin-top: 8px;
        padding-top: 20px;
        gap: 8px;
    }
    .pg {
        font-size: 13px;
        letter-spacing: 1px;
    }
    .pg.num { color: var(--dim); }
    .pg.off { color: var(--line); }
    a.pg {
        color: var(--accent);
        text-decoration: none;
        border: 1px solid var(--line);
        padding: 6px 12px;
    }
    a.pg:hover {
        background: var(--accent);
        color: var(--bg);
        text-shadow: none;
    }
    footer {
        text-align: center;
        color: var(--dim);
        font-size: 11px;
        margin-top: 40px;
        border-top: 1px solid var(--line);
        padding-top: 16px;
        letter-spacing: 1px;
    }
    /* Curseur clignotant retro */
    .cursor {
        display: inline-block;
        width: 9px;
        height: 15px;
        background: var(--accent);
        vertical-align: middle;
        animation: blink 1s steps(1) infinite;
    }
    @keyframes blink { 50% { opacity: 0; } }
</style>
</head>
<body>

<header>
    <h1>3614 MISTRAL</h1>
    <div class="sub">&#183; LIVRE D'OR &#183;</div>
</header>

<div class="count">
<?php echo $total; ?> &Eacute;CHANGE<?php echo $total > 1 ? 'S' : ''; ?> RECENS&Eacute;<?php echo $total > 1 ? 'S' : ''; ?>
<?php if ($totalPages > 1): ?>
    &#183; PAGE <?php echo $page; ?>/<?php echo $totalPages; ?>
<?php endif; ?>
    <span class="cursor"></span>
</div>

<?php if ($total === 0): ?>
    <div class="empty">
        Aucun &eacute;change enregistr&eacute; pour l'instant.<br>
        Connectez-vous au 3614 MISTRAL...
    </div>
<?php else: ?>
    <?php foreach ($entries as $en): ?>
        <div class="entry">
            <div class="meta">
                <span class="date"><?php
                    echo e($en['ts'] > 0 ? date('d/m/Y H:i:s', $en['ts']) : $en['date']);
                    if ($en['session'] !== '') {
                        echo ' &#183; <span class="sess">' . e($en['session']) . '</span>';
                    }
                ?></span>
                <span class="model">
                    <?php if (!empty($en['duration_ms'])): ?>
                        <?php echo (int) $en['duration_ms']; ?>&#8239;ms
                    <?php endif; ?>
                    <?php if (!empty($en['tokens'])): ?>
                        &#183; <?php echo (int) $en['tokens']; ?>&#8239;tokens
                    <?php endif; ?>
                    <?php if ($en['model'] !== ''): ?>
                        &#183; <?php echo e($en['model']); ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($en['user'] !== ''): ?>
                <div class="label">&gt; USER</div>
                <div class="q"><?php echo e($en['user']); ?></div>
            <?php endif; ?>
            <?php if ($en['mistral'] !== ''): ?>
                <div class="label">&gt; MISTRAL</div>
                <div class="a"><?php echo e($en['mistral']); ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="pager">
            <?php if ($page > 1): ?>
                <a href="?p=<?php echo $page - 1; ?>" class="pg prev">&laquo; RETOUR</a>
            <?php else: ?>
                <span class="pg off">&laquo; RETOUR</span>
            <?php endif; ?>

            <span class="pg num">PAGE <?php echo $page; ?> / <?php echo $totalPages; ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="?p=<?php echo $page + 1; ?>" class="pg next">SUITE &raquo;</a>
            <?php else: ?>
                <span class="pg off">SUITE &raquo;</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<footer>
    MINITEL &#183; SERVICE T&Eacute;L&Eacute;MATIQUE &#183; MINIPAVI<br>
    G&eacute;n&eacute;r&eacute; le <?php echo date('d/m/Y \a\ H:i'); ?>
</footer>

</body>
</html>
