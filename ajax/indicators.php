<?php

function generateCsv($data, $delimiter = ',', $enclosure = '"') {
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
$indicatorActivity = new Indicator(
        'Ev�nements', "SELECT DATE_FORMAT(a.activity_date, '%d/%m/%Y') AS date, e.nom_equipe, c.libelle AS competition, a.comment AS description, de.responsable AS utilisateur, de.email AS email_utilisateur 
        FROM activity a
        JOIN details_equipes de ON de.id_equipe=a.user_id
        JOIN equipes e ON e.id_equipe=a.user_id
        JOIN competitions c ON c.code_competition=e.code_competition
        ORDER BY a.activity_date DESC");
$indicatorComptes = new Indicator(
        'Comptes', "SELECT e.nom_equipe, de.email, ca.login, '****' AS password FROM equipes e
        JOIN details_equipes de ON de.id_equipe=e.id_equipe
        JOIN comptes_acces ca ON ca.id_equipe=e.id_equipe");
$indicatorMatchesDupliques = new Indicator(
        'Matches dupliqu�s', "SELECT e1.nom_equipe, e2.nom_equipe, m.code_match, COUNT(*) FROM matches m
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        GROUP BY m.id_equipe_dom, m.id_equipe_ext, m.code_competition
        HAVING COUNT(*) > 1");
$indicatorEquipesSansClub = new Indicator(
        'Club non renseign�', "SELECT e.nom_equipe,
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
$indicatorInfosManquantes = new Indicator(
        'Infos Manquantes', "SELECT d.responsable, d.email, e.nom_equipe, c.libelle, cl.division FROM details_equipes d
        LEFT JOIN equipes e ON e.id_equipe=d.id_equipe
        LEFT JOIN competitions c ON c.code_competition=e.code_competition
        LEFT JOIN classements cl ON cl.id_equipe=e.id_equipe
        WHERE (email='' OR responsable='') AND cl.division IS NOT NULL"
);
$indicatorEquipesEngageesChampionnat = new Indicator(
        'Equipes', "SELECT e.nom_equipe,
        comp.libelle,
        c.division,
        d.jour_reception,
        d.heure_reception,
        d.responsable,
        d.email,
        d.telephone_1,
        d.gymnase
        FROM equipes e
        LEFT JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN classements c ON c.id_equipe=e.id_equipe AND c.code_competition=e.code_competition
        LEFT JOIN details_equipes d ON d.id_equipe=e.id_equipe
        WHERE ((e.code_competition='m' OR e.code_competition='f') AND c.division IS NOT NULL)
        ORDER BY e.code_competition, c.division, e.id_equipe"
);
$indicatorLicencesDupliquees = new Indicator(
        'Licences dupliqu�es', "SELECT num_licence, COUNT(*) AS nb_duplicats FROM joueurs 
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
$results[] = $indicatorActivity->getResult();
$results[] = $indicatorLicencesDupliquees->getResult();
$results[] = $indicatorMatchesNonRenseignes->getResult();
$results[] = $indicatorEquipesEngageesChampionnat->getResult();
$results[] = $indicatorInfosManquantes->getResult();
$results[] = $indicatorEquipesSansClub->getResult();
$results[] = $indicatorMatchesDupliques->getResult();
$results[] = $indicatorComptes->getResult();
$indicatorName = filter_input(INPUT_GET, 'indicator');
if (!$indicatorName) {
    echo json_encode(array('results' => $results));
    exit();
}
foreach ($results as $result) {
    if ($result['fieldLabel'] === $indicatorName) {
        echo generateCsv($result['details']);
        exit();
    }
}
