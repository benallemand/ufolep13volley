<?php

header('Content-Type: text/html; charset=utf-8');

function generateCsv($data, $delimiter = ';', $enclosure = '"')
{
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


$indicatorPossibleDuplicatePlayers = new Indicator(
    'Joueurs potentiellement en doublon', "
        SELECT concat(prenom, ' ', nom) AS Joueur,
        count(*) AS Occurences
        FROM joueurs
        GROUP BY concat(prenom, ' ', nom)
        HAVING count(*) > 1
        ORDER BY nom ASC");


$indicatorSuspectTransfert = new Indicator(
    'Transferts suspect de joueurs', "SELECT
SUBSTRING(comment, 10, LOCATE('(', comment) - 11) AS joueur,
GROUP_CONCAT(SUBSTRING(comment, LOCATE('equipe ',comment)+7)) AS equipes,
GROUP_CONCAT(DATE_FORMAT(activity_date, '%d/%m/%Y')) AS dates
FROM activity 
WHERE 
comment LIKE 'Ajout de %'
AND MID(comment, LOCATE('(',comment)+1, 8) REGEXP '[0-9]+'
GROUP BY 
MID(comment, LOCATE('(',comment)+1, 8),
SUBSTRING(SUBSTRING_INDEX(comment, '(', -1), 1, LENGTH(SUBSTRING_INDEX(comment, '(', -1))-1)
HAVING COUNT(*) > 1");
$indicatorWrongMatchTime = new Indicator(
    'Horaires de match incorrects', "SELECT
m.code_match,
edom.nom_equipe AS equipe_dom,
eext.nom_equipe AS equipe_ext,
ELT(WEEKDAY(m.date_reception)+2, 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi') AS jour_match,
REPLACE(m.heure_reception, 'h', ':') AS heure_match,
c.jour AS jour_creneau,
c.heure AS heure_creneau
FROM matches m
JOIN creneau c ON c.id_equipe = m.id_equipe_dom
JOIN equipes edom ON edom.id_equipe = m.id_equipe_dom
JOIN equipes eext ON eext.id_equipe = m.id_equipe_ext
WHERE 
    REPLACE(m.heure_reception, 'h', ':') != c.heure
    AND ELT(WEEKDAY(m.date_reception)+2, 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi') = c.jour
    AND m.certif+0 = 0
");
$indicatorDuplicateMatchCode = new Indicator(
    'Code match dupliqués', "SELECT
m.code_match,
c.libelle AS competition,
m.division,
edom.nom_equipe AS domicile,
eext.nom_equipe AS exterieur,
CONCAT(DATE_FORMAT(m.date_reception, '%d/%m/%Y'), ' à ', m.heure_reception) AS reception_le
FROM matches m
JOIN competitions c ON c.code_competition = m.code_competition
JOIN equipes edom ON edom.id_equipe = m.id_equipe_dom
JOIN equipes eext ON eext.id_equipe = m.id_equipe_ext
WHERE m.code_match IN
(SELECT code_match FROM matches GROUP BY code_match HAVING COUNT(*) > 1)
ORDER BY m.code_match"
);
$indicatorPlayersWithoutLicence = new Indicator(
    'Joueurs sans numéro de licence', "SELECT
        CONCAT(j.nom, ' ', j.prenom) AS joueur, 
        c.nom AS club,
        CONCAT(e.nom_equipe, ' (', comp.libelle, ')') AS equipe,
        jresp.email AS responsable
        FROM joueur_equipe je 
        JOIN joueurs j ON j.id = je.id_joueur
        JOIN equipes e ON e.id_equipe = je.id_equipe
        JOIN joueur_equipe jeresp ON jeresp.id_equipe = e.id_equipe AND jeresp.is_leader+0 > 0
        JOIN joueurs jresp ON jresp.id = jeresp.id_joueur
        JOIN competitions comp ON comp.code_competition = e.code_competition
        JOIN clubs c ON c.id = j.id_club
        WHERE j.num_licence = ''
        ORDER BY equipe ASC");
$indicatorDoublonsJoueursEquipes = new Indicator(
    'Doublons dans une équipe', "SELECT id_joueur, id_equipe, COUNT(*) AS cnt FROM joueur_equipe
        GROUP BY id_joueur, id_equipe
        HAVING cnt > 1");
$indicatorEquipesEngageesChampionnat = new Indicator(
    'Equipes', "SELECT e.nom_equipe,
        '' AS my_trim,
        clubs.nom AS club,
        e.id_equipe AS id,
        e.code_competition AS compet,
        c.division,
        GROUP_CONCAT(cr.jour SEPARATOR ',') AS jour,
        GROUP_CONCAT(cr.heure SEPARATOR ',') AS heure,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.email,
        jresp.telephone,
        GROUP_CONCAT(gym.nom SEPARATOR ',') AS gymnase
        FROM equipes e
        JOIN joueur_equipe je ON je.id_equipe=e.id_equipe
        JOIN joueurs jresp ON jresp.id = je.id_joueur AND je.is_leader+0 = 1
        LEFT JOIN creneau cr ON cr.id_equipe=e.id_equipe
        LEFT JOIN gymnase gym ON gym.id=cr.id_gymnase
        JOIN clubs ON clubs.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        JOIN classements c ON c.id_equipe=e.id_equipe AND c.code_competition=e.code_competition
        WHERE ((e.code_competition='m' OR e.code_competition='f') AND c.division IS NOT NULL)
        GROUP BY id
        ORDER BY e.code_competition, c.division, e.id_equipe");
$indicatorPlayersWithTeamButNoClub = new Indicator(
    'Joueurs avec équipe mais sans club', "SELECT DISTINCT
        j.prenom, 
        j.nom, 
        CONCAT(j.departement_affiliation, '_', j.num_licence) AS num_licence, 
        e.nom_equipe AS nom_equipe
        FROM joueurs j
        JOIN joueur_equipe je ON je.id_joueur = j.id
        JOIN equipes e ON e.id_equipe = je.id_equipe
        WHERE j.id_club = 0
        ORDER BY j.id ASC");
$indicatorNotValidatedPlayers = new Indicator(
    'Joueurs en attente de validation', "SELECT DISTINCT
        j.prenom, 
        j.nom, 
        CONCAT(j.departement_affiliation, '_', j.num_licence) AS num_licence, 
        c.nom AS nom_club
        FROM joueurs j
        JOIN joueur_equipe je ON je.id_joueur = j.id
        JOIN clubs c ON c.id = j.id_club
        WHERE j.est_actif+0 = 0
        ORDER BY j.id ASC");
$indicatorActivity = new Indicator(
    'Evènements', "SELECT
        DATE_FORMAT(a.activity_date, '%d/%m/%Y') AS date, 
        e.nom_equipe, 
        c.libelle AS competition, 
        a.comment AS description, 
        ca.login AS utilisateur, 
        ca.email AS email_utilisateur 
        FROM activity a
        LEFT JOIN comptes_acces ca ON ca.id=a.user_id
        LEFT JOIN equipes e ON e.id_equipe=ca.id_equipe
        LEFT JOIN competitions c ON c.code_competition=e.code_competition
        ORDER BY a.id DESC");
$indicatorComptes = new Indicator(
    'Comptes', "SELECT
            e.nom_equipe, 
            ca.email, 
            ca.login,
            p.name AS profil
            FROM equipes e
            JOIN comptes_acces ca ON ca.id_equipe=e.id_equipe
            LEFT JOIN users_profiles up ON up.user_id = ca.id
            LEFT JOIN profiles p ON p.id = up.profile_id
            ORDER BY e.nom_equipe");
$indicatorMatchesDupliques = new Indicator(
    'Matches dupliqués', "SELECT e1.nom_equipe, e2.nom_equipe, m.code_match, COUNT(*) FROM matches m
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        GROUP BY m.id_equipe_dom, m.id_equipe_ext, m.code_competition
        HAVING COUNT(*) > 1");
$indicatorEquipesSansClub = new Indicator(
    'Club non renseigné', "SELECT
            e.nom_equipe,
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
         HAVING COUNT(*) > 1 AND num_licence != ''"
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
$indicatorActiveTeamWithoutTeamManagerAccount = new Indicator(
    'Equipes actives sans compte responsable équipe', "SELECT
  e.nom_equipe AS equipe,
  c.libelle AS competition
FROM equipes e
  JOIN competitions c ON c.code_competition = e.code_competition
WHERE
  e.id_equipe NOT IN (
    SELECT ca.id_equipe
    FROM comptes_acces ca
    WHERE ca.id IN (
      SELECT up.user_id
      FROM users_profiles up
      WHERE profile_id IN (
        SELECT p.id
        FROM profiles p
        WHERE p.name = 'RESPONSABLE_EQUIPE'
      )
    )
  )
  AND e.id_equipe IN (
    SELECT cl.id_equipe
    FROM classements cl
  )"
);
$results = array();
$results[] = $indicatorEquipesEngageesChampionnat->getResult();
$results[] = $indicatorPlayersWithTeamButNoClub->getResult();
$results[] = $indicatorNotValidatedPlayers->getResult();
$results[] = $indicatorActivity->getResult();
$results[] = $indicatorLicencesDupliquees->getResult();
$results[] = $indicatorMatchesNonRenseignes->getResult();
$results[] = $indicatorEquipesSansClub->getResult();
$results[] = $indicatorMatchesDupliques->getResult();
$results[] = $indicatorComptes->getResult();
$results[] = $indicatorDoublonsJoueursEquipes->getResult();
$results[] = $indicatorPlayersWithoutLicence->getResult();
$results[] = $indicatorDuplicateMatchCode->getResult();
$results[] = $indicatorWrongMatchTime->getResult();
$results[] = $indicatorSuspectTransfert->getResult();
$results[] = $indicatorPossibleDuplicatePlayers->getResult();
$results[] = $indicatorActiveTeamWithoutTeamManagerAccount->getResult();
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
