<?php

header('Content-Type: text/html; charset=utf-8');

function generateCsv($data, $delimiter = ',', $enclosure = '"') {
    $contents = '';
    $handle = fopen('php://temp', 'r+');
    $isHeaderWritten = false;
    foreach ($data as $line) {
        $dateYesterday = new DateTime();
        $dateYesterday->sub(new DateInterval('P1D'));
        $dateActivity = DateTime::createFromFormat("d/m/Y", $line['date']);
        if ($dateActivity < $dateYesterday) {
            continue;
        }
        if (!$isHeaderWritten) {
            fputcsv($handle, array_keys($line), $delimiter, $enclosure);
            $isHeaderWritten = true;
        }
        fputcsv($handle, $line, $delimiter, $enclosure);
    }
    rewind($handle);
    while (!feof($handle)) {
        $contents .= fread($handle, 8192);
    }
    fclose($handle);
    return $contents;
}

require_once 'classes/Indicator.php';
$indicatorNotValidatedPlayers = new Indicator(
        'Joueurs en attente de validation', "SELECT 
        j.prenom, j.nom, CONCAT(j.departement_affiliation, '_', j.num_licence) AS num_licence, c.nom AS nom_club
        FROM joueurs j
        LEFT JOIN clubs c ON c.id = j.id_club
        WHERE j.est_actif+0 = 0
        ORDER BY j.id ASC");
$indicatorActivity = new Indicator(
        'Evènements', "SELECT 
        DATE_FORMAT(a.activity_date, '%d/%m/%Y') AS date, 
        e.nom_equipe, 
        c.libelle AS competition, 
        a.comment AS description, 
        ca.login AS utilisateur, 
        de.email AS email_utilisateur 
        FROM activity a
        LEFT JOIN comptes_acces ca ON ca.id=a.user_id
        LEFT JOIN details_equipes de ON de.id_equipe=ca.id_equipe
        LEFT JOIN equipes e ON e.id_equipe=ca.id_equipe
        LEFT JOIN competitions c ON c.code_competition=e.code_competition
        ORDER BY a.id DESC");
$indicatorComptes = new Indicator(
        'Comptes', "SELECT e.nom_equipe, ca.email, ca.login FROM equipes e
        JOIN comptes_acces ca ON ca.id_equipe=e.id_equipe");
$indicatorMatchesDupliques = new Indicator(
        'Matches dupliqués', "SELECT e1.nom_equipe, e2.nom_equipe, m.code_match, COUNT(*) FROM matches m
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        GROUP BY m.id_equipe_dom, m.id_equipe_ext, m.code_competition
        HAVING COUNT(*) > 1");
$indicatorEquipesSansClub = new Indicator(
        'Club non renseigné', "SELECT e.nom_equipe,
        comp.libelle,
        c.division
        FROM equipes e
        LEFT JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN classements c ON c.id_equipe=e.id_equipe AND c.code_competition=e.code_competition
        WHERE (
        (e.code_competition='m' OR e.code_competition='f' OR e.code_competition='kh') 
        AND c.division IS NOT NULL
        AND e.id_club IS NULL
        )
        ORDER BY e.code_competition, c.division, e.id_equipe"
);
$indicatorLicencesDupliquees = new Indicator(
        'Licences dupliquées', "SELECT num_licence, COUNT(*) AS nb_duplicats FROM joueurs 
         GROUP BY num_licence
         HAVING COUNT(*) > 1 AND num_licence != 'Encours'"
);
$indicatorMatchesNonRenseignes = new Indicator(
        'Retards', "SELECT 
        m.code_match AS code, 
        c.libelle AS competition, 
        m.division AS division_poule,
        e1.nom_equipe AS domicile, 
        e2.nom_equipe AS exterieur, 
        DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS 'date'
        FROM matches m
        LEFT JOIN equipes e1 ON e1.id_equipe=m.id_equipe_dom
        LEFT JOIN equipes e2 ON e2.id_equipe=m.id_equipe_ext
        LEFT JOIN competitions c ON c.code_competition=m.code_competition
        WHERE 
        (
        (m.score_equipe_dom+m.score_equipe_ext+0=0)
        OR
        ((m.set_1_dom+m.set_1_ext=0) AND (m.score_equipe_dom+m.score_equipe_ext>0))
        OR
        ((m.set_1_dom+m.set_1_ext>0) AND (m.score_equipe_dom+m.score_equipe_ext+0=0))
        )
        AND m.date_reception < CURDATE() - INTERVAL 10 DAY"
);
$results = array();
$results[] = $indicatorNotValidatedPlayers->getResult();
$results[] = $indicatorActivity->getResult();
$results[] = $indicatorLicencesDupliquees->getResult();
$results[] = $indicatorMatchesNonRenseignes->getResult();
$results[] = $indicatorEquipesSansClub->getResult();
$results[] = $indicatorMatchesDupliques->getResult();
$results[] = $indicatorComptes->getResult();
$indicatorName = utf8_decode(filter_input(INPUT_GET, 'indicator'));
if (!$indicatorName) {
    echo json_encode(utf8_encode_mix(array('results' => $results)));
    exit();
}
foreach ($results as $result) {
    if ($result['fieldLabel'] === $indicatorName) {
        echo generateCsv($result['details']);
        exit();
    }
}
