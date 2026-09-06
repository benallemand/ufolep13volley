<?php
// Issue #266 : la grille ExtJS a été remplacée par un composant Vue de la SPA
// publique. On conserve l'URL historique en redirection : elle circule en lien
// externe et en favori (/rank_for_cup.php?code_competition=c).
$code_competition = filter_input(INPUT_GET, 'code_competition');
// Le code de compétition part dans un en-tête Location : on n'y laisse passer
// que des caractères de code (pas de CR/LF, pas de séparateur d'URL).
if (empty($code_competition) || preg_match('/^[a-z0-9_]{1,20}$/i', $code_competition) !== 1) {
    header('Location: /pages/home.html');
    exit(0);
}
header("Location: /pages/home.html#/rank_for_cup/$code_competition");
exit(0);
