<?php

require_once 'classes/Indicator.php';
$indicatorLicencesDupliquees = new Indicator(
        'Licences dupliquees', "SELECT num_licence, COUNT(*) AS nb_duplicats FROM joueurs 
         GROUP BY num_licence
         HAVING COUNT(*) > 1 AND num_licence != 'Encours'"
);
$indicatorMatchesNonRenseignes = new Indicator(
        'Matches non renseignes', "SELECT 
        m.code_match AS code, 
        c.libelle AS competition, 
        e1.nom_equipe AS domicile, 
        e2.nom_equipe AS exterieur, 
        DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS 'date'
        FROM matches m
        LEFT JOIN equipes e1 ON e1.id_equipe=m.id_equipe_dom
        LEFT JOIN equipes e2 ON e2.id_equipe=m.id_equipe_ext
        LEFT JOIN competitions c ON c.code_competition=m.code_competition
        WHERE m.score_equipe_dom+0 = 0
        AND m.score_equipe_ext+0 = 0
        AND m.date_reception < CURDATE() - INTERVAL 10 DAY"
);
$results = array();

$results[] = $indicatorLicencesDupliquees->getResult();
$results[] = $indicatorMatchesNonRenseignes->getResult();
echo json_encode(array('results' => $results));
