<?php

header('Content-Type: text/html; charset=utf-8');

/**
 * @param $data
 * @param string $delimiter
 * @param string $enclosure
 * @return string
 * @throws Exception
 */
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

require_once __DIR__ . '/../classes/Indicator.php';


$indicatorPossibleDuplicatePlayers = new Indicator(
    'Joueurs potentiellement en doublon', "
        SELECT j.prenom, 
               j.nom, 
               j2.prenom, 
               j2.nom 
        FROM joueurs j, joueurs j2 
        WHERE j.id != j2.id 
          AND REPLACE(UPPER(j.nom), ' ', '') = REPLACE(UPPER(j2.nom), ' ', '') 
          AND REPLACE(UPPER(j.prenom), ' ', '') = REPLACE(UPPER(j2.prenom), ' ', '')
        ORDER BY j.nom ASC");


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
HAVING COUNT(DISTINCT SUBSTRING(comment, LOCATE('equipe ',comment)+7)) > 1");

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
        WHERE (j.num_licence IS NULL 
              OR j.num_licence = '')
        AND e.id_equipe IN (SELECT id_equipe FROM classements)
        AND CURRENT_DATE >= comp.start_date
        ORDER BY equipe ASC");

$indicatorEquipesEngageesChampionnat = new Indicator(
    'Equipes', "SELECT e.nom_equipe,
                                     ''                                   AS my_trim,
                                     clubs.nom                            AS club,
                                     e.id_equipe                          AS id,
                                     e.code_competition                   AS compet,
                                     c.division,
                                     GROUP_CONCAT(cr.jour SEPARATOR ',')  AS jour,
                                     GROUP_CONCAT(cr.heure SEPARATOR ',') AS heure,
                                     CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
                                     jresp.email,
                                     jresp.telephone,
                                     GROUP_CONCAT(gym.nom SEPARATOR ',')  AS gymnase
                              FROM equipes e
                                       JOIN joueur_equipe je ON je.id_equipe = e.id_equipe
                                       JOIN joueurs jresp ON jresp.id = je.id_joueur AND je.is_leader + 0 = 1
                                       LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
                                       LEFT JOIN gymnase gym ON gym.id = cr.id_gymnase
                                       JOIN clubs ON clubs.id = e.id_club
                                       JOIN competitions comp ON comp.code_competition = e.code_competition
                                       JOIN classements c ON c.id_equipe = e.id_equipe AND c.code_competition = e.code_competition
                              WHERE ((e.code_competition = 'm' OR e.code_competition = 'f' OR e.code_competition = 'mo') AND c.division IS NOT NULL)
                              GROUP BY e.nom_equipe, '', clubs.nom, e.id_equipe, e.code_competition, c.division, CONCAT(jresp.prenom, ' ', jresp.nom), jresp.email, jresp.telephone
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
        j.club AS nom_club
        FROM players_view j
        JOIN joueur_equipe je ON je.id_joueur = j.id
        JOIN clubs c ON c.id = j.id_club
        JOIN equipes e on je.id_equipe = e.id_equipe
        JOIN competitions c2 on e.code_competition = c2.code_competition
        WHERE j.est_actif = 0
        AND je.id_equipe IN (SELECT id_equipe FROM classements)
        AND CURRENT_DATE >= c2.start_date
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
        WHERE m.code_competition != 'mo'
        AND m.match_status != 'ARCHIVED'
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
          AND m.match_status = 'CONFIRMED'
          AND m.date_reception < CURDATE() - INTERVAL 5 DAY"
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

$indicatorActiveTeamWithoutTeamLeader = new Indicator(
    'Equipes actives sans responsable', "SELECT
  je.id_equipe,
  e.nom_equipe AS equipe,
  c.libelle AS competition
  FROM joueur_equipe je
  JOIN equipes e ON e.id_equipe = je.id_equipe
  JOIN competitions c ON c.code_competition = e.code_competition
  WHERE je.id_equipe IN (
    SELECT cl.id_equipe
    FROM classements cl
  )
  GROUP BY id_equipe
  HAVING SUM(je.is_leader+0) IS NULL
  "
);

$indicatorActiveTeamWithoutTimeslot = new Indicator(
    'Equipes actives sans créneau de réception', "SELECT
  e.nom_equipe AS equipe,
  c.libelle AS competition
  FROM equipes e
  JOIN competitions c ON c.code_competition = e.code_competition
  WHERE e.id_equipe IN (
    SELECT cl.id_equipe
    FROM classements cl
  )
  AND e.id_equipe NOT IN (SELECT id_equipe FROM creneau)
  "
);

$indicatorPendingMatchesWithWrongTimeSlot = new Indicator(
    'Matches non certifiés dont la date ne correspond pas à un créneau', "SELECT 
    m.code_match,
    e.nom_equipe AS equipe_domicile,
    c.libelle AS competition
    FROM matches m
      JOIN equipes e ON e.id_equipe = m.id_equipe_dom
      JOIN competitions c ON c.code_competition = m.code_competition
      LEFT JOIN creneau cr ON
                             cr.id_equipe = m.id_equipe_dom AND
                             cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
                                           'Dimanche',
                                           'Lundi',
                                           'Mardi',
                                           'Mercredi',
                                           'Jeudi',
                                           'Vendredi',
                                           'Samedi')
    WHERE cr.id IS NULL AND m.certif + 0 = 0
    AND m.match_status != 'ARCHIVED'
    AND m.date_reception > CURRENT_DATE
    ORDER BY m.code_match"
);

$indicatorTimeSlotWithConstraint = new Indicator(
    'Créneaux avec une contrainte horaire forte', "SELECT
      c2.libelle AS competition,
      e.nom_equipe AS equipe,
      c1.nom AS club,
      g.nom AS gymnase,
      g.ville,
      c.jour,
      c.heure
    FROM creneau c
      JOIN gymnase g ON g.id = c.id_gymnase
      JOIN equipes e ON e.id_equipe = c.id_equipe
      JOIN clubs c1 ON c1.id = e.id_club
      JOIN competitions c2 ON c2.code_competition = e.code_competition
    WHERE has_time_constraint + 0 > 0
    "
);

$indicatorTeamLeadersByChamp = new Indicator(
    'Emails des responsables par compétition', "SELECT
      GROUP_CONCAT(j.email) AS emails,
      c.libelle             AS competition
    FROM joueur_equipe je
      JOIN joueurs j ON j.id = je.id_joueur
      JOIN equipes e ON e.id_equipe = je.id_equipe
      JOIN classements c2 on e.id_equipe = c2.id_equipe
      JOIN competitions c ON c.code_competition = c2.code_competition
    WHERE
      je.is_leader + 0 > 0
      AND j.email IS NOT NULL
    GROUP BY c.libelle");

$indicatorMatchesByGymnasiumByDate = new Indicator(
    'Nombre de matches par date et par gymnase', "SELECT
  gymnase.ville AS \"Ville\",
  gymnase.nom AS \"Gymnase\",
  m.date_reception AS \"Date\",
  COUNT(DISTINCT m.id_match) AS \"Nombre de matches\",
  GROUP_CONCAT(DISTINCT m.code_match SEPARATOR ', ') AS \"Liste des matches\"
FROM matches m
  JOIN creneau ON creneau.id_equipe = m.id_equipe_dom
  JOIN gymnase ON gymnase.id = creneau.id_gymnase
WHERE m.match_status != 'ARCHIVED'
GROUP BY CONCAT(gymnase.nom, gymnase.ville), m.date_reception
ORDER BY COUNT(DISTINCT m.id_match) DESC");

$indicatorTooMuchMatchesByGymnasiumByDate = new Indicator(
    'Nombre de matches trop élevés par date et par gymnase', "SELECT
  gymnase.ville AS \"Ville\",
  gymnase.nom AS \"Gymnase\",
  m.date_reception AS \"Date\",
  COUNT(DISTINCT m.id_match) AS \"Nombre de matches\",
  gymnase.nb_terrain AS \"Nombre de terrains\",
  GROUP_CONCAT(DISTINCT m.code_match SEPARATOR ', ') AS \"Liste des matches\"
FROM matches m
  JOIN creneau ON creneau.id_equipe = m.id_equipe_dom
  JOIN gymnase ON gymnase.id = creneau.id_gymnase
WHERE m.match_status != 'ARCHIVED'
AND m.date_reception > CURRENT_DATE
GROUP BY CONCAT(gymnase.nom, gymnase.ville), m.date_reception
HAVING COUNT(DISTINCT m.id_match) > gymnase.nb_terrain
ORDER BY COUNT(DISTINCT m.id_match) DESC");

$indicatorEquityBetweenHomeAndAway = new Indicator(
    "Equipes avec trop d'écart entre réception et déplacement", "SELECT 
       SUM(IF(m.id_equipe_dom = e.id_equipe, 1, 0)) AS domicile,
       SUM(IF(m.id_equipe_ext = e.id_equipe, 1, 0)) AS exterieur,
       c.code_competition                           AS competition,
       c.division                                   AS division,
       e.nom_equipe                                 AS equipe
FROM matches m
         JOIN equipes e on m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe
         JOIN classements c on e.id_equipe = c.id_equipe
WHERE m.match_status IN ('CONFIRMED', 'NOT_CONFIRMED')
GROUP BY c.code_competition, c.division, e.nom_equipe
HAVING ABS(domicile - exterieur) > 2
ORDER BY competition, division");

$indicatorMatchGenerationCriticity = new Indicator(
    "Criticité de génération des matchs",
    "SELECT  e.nom_equipe, 
                  COUNT(c1.id_equipe) AS nb_equipes_meme_creneau,
                  g.nb_terrain,
                  g.nb_terrain - COUNT(c1.id_equipe) AS ratio
        FROM equipes e
        LEFT JOIN creneau c on e.id_equipe = c.id_equipe
        LEFT JOIN creneau c1 on 
            c1.id_gymnase = c.id_gymnase 
                AND c1.jour = c.jour 
                AND c1.id_equipe != c.id_equipe 
                AND c1.id_equipe IN (SELECT id_equipe FROM classements)
        LEFT JOIN gymnase g on c.id_gymnase = g.id
        LEFT JOIN equipes e2 ON e2.id_equipe = c1.id_equipe
        WHERE e.id_equipe IN (SELECT id_equipe FROM classements)
        GROUP BY e.nom_equipe 
        ORDER BY ratio");

$indicatorErrorInEmails = new Indicator(
    "Emails en erreur",
    "SELECT  
            id,
            from_email,
            to_email,
            cc,
            bcc,
            subject,
            body,
            creation_date,
            sent_date
        FROM emails
        WHERE sending_status = 'ERROR'
        ORDER BY creation_date DESC");

$indicatorMatchesWithInvalidPlayers = new Indicator(
    "Matchs avec joueurs non homologués",
    "SELECT m.code_match,
                 m.date_reception,
                 j.prenom,
                 j.nom,
                 c.nom as club,
                 j.date_homologation
          from matches m
                   join match_player mp on m.id_match = mp.id_match
                   join players_view j on mp.id_player = j.id
                   join clubs c on j.id_club = c.id
          where (j.date_homologation > m.date_reception OR j.est_actif = 0) 
                AND m.match_status = 'CONFIRMED'
          order by nom");

$indicatorMatchesWithInvalidDate = new Indicator(
    "Problèmes dans les dates des matchs",
    "SELECT a.* FROM (SELECT m.code_match,
       m.date_reception,
       edom.nom_equipe  AS domicile,
       eext.nom_equipe  AS exterieur,
       m.match_status   AS statut,
       'date interdite' AS raison
from matches m
         JOIN equipes edom on m.id_equipe_dom = edom.id_equipe
         JOIN equipes eext on m.id_equipe_ext = eext.id_equipe
WHERE (m.date_reception IN (SELECT closed_date FROM blacklist_date))
  AND m.match_status != 'ARCHIVED'
UNION ALL
SELECT m.code_match,
       m.date_reception,
       edom.nom_equipe   AS domicile,
       eext.nom_equipe   AS exterieur,
       m.match_status    AS statut,
       'gymnase indispo' AS raison
from matches m
         JOIN equipes edom on m.id_equipe_dom = edom.id_equipe
         JOIN equipes eext on m.id_equipe_ext = eext.id_equipe
         JOIN creneau c on edom.id_equipe = c.id_equipe
         JOIN blacklist_gymnase bg on c.id_gymnase = bg.id_gymnase
WHERE bg.closed_date = m.date_reception
  AND m.match_status != 'ARCHIVED'
UNION ALL
SELECT m.code_match,
       m.date_reception,
       edom.nom_equipe           AS domicile,
       eext.nom_equipe           AS exterieur,
       m.match_status            AS statut,
       'equipe domicile indispo' AS raison
from matches m
         JOIN equipes edom on m.id_equipe_dom = edom.id_equipe
         JOIN equipes eext on m.id_equipe_ext = eext.id_equipe
         JOIN blacklist_team bt on edom.id_equipe = bt.id_team
WHERE bt.closed_date = m.date_reception
  AND m.match_status != 'ARCHIVED'
UNION ALL
SELECT m.code_match,
       m.date_reception,
       edom.nom_equipe            AS domicile,
       eext.nom_equipe            AS exterieur,
       m.match_status             AS statut,
       'equipe extérieur indispo' AS raison
from matches m
         JOIN equipes edom on m.id_equipe_dom = edom.id_equipe
         JOIN equipes eext on m.id_equipe_ext = eext.id_equipe
         JOIN blacklist_team bt on eext.id_equipe = bt.id_team
WHERE bt.closed_date = m.date_reception
  AND m.match_status != 'ARCHIVED'
UNION ALL
SELECT CONCAT(m_t1.code_match, ' et ', m_t2.code_match) AS code_match,
       m_t1.date_reception,
       edom.nom_equipe                                  AS domicile,
       eext.nom_equipe                                  AS exterieur,
       m_t1.match_status                               AS statut,
       'equipes qui ne peuvent pas jouer le même soir' AS raison
FROM matches m_t1,
     matches m_t2,
     blacklist_teams bt
         JOIN equipes edom on bt.id_team_1 = edom.id_equipe
         JOIN equipes eext on bt.id_team_2 = eext.id_equipe
WHERE (m_t1.id_equipe_dom = bt.id_team_1 OR m_t1.id_equipe_ext = bt.id_team_1)
  AND (m_t2.id_equipe_dom = bt.id_team_2 OR m_t2.id_equipe_ext = bt.id_team_2)
  AND m_t1.date_reception = m_t2.date_reception
  AND m_t1.match_status != 'ARCHIVED'
  AND m_t2.match_status != 'ARCHIVED'
GROUP BY date_reception
order by code_match) a WHERE date_reception > CURRENT_DATE");

$indicatorTeamManyMatchesSameDay = new Indicator(
    "Equipe qui joue plusieurs matchs le même soir",
    "SELECT GROUP_CONCAT(m.code_match) AS code_match,
                e.nom_equipe,
                m.date_reception
         FROM equipes e
                  JOIN matches m on e.id_equipe = m.id_equipe_dom OR e.id_equipe = m.id_equipe_ext
         WHERE m.date_reception > CURRENT_DATE
         GROUP BY e.id_equipe, m.date_reception
         HAVING COUNT(m.code_match) > 1
         ORDER BY date_reception");

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
$results[] = $indicatorPlayersWithoutLicence->getResult();
$results[] = $indicatorSuspectTransfert->getResult();
$results[] = $indicatorPossibleDuplicatePlayers->getResult();
$results[] = $indicatorActiveTeamWithoutTeamManagerAccount->getResult();
$results[] = $indicatorPendingMatchesWithWrongTimeSlot->getResult();
$results[] = $indicatorTimeSlotWithConstraint->getResult();
$results[] = $indicatorTeamLeadersByChamp->getResult();
$results[] = $indicatorActiveTeamWithoutTeamLeader->getResult();
$results[] = $indicatorMatchesByGymnasiumByDate->getResult();
$results[] = $indicatorTooMuchMatchesByGymnasiumByDate->getResult();
$results[] = $indicatorActiveTeamWithoutTimeslot->getResult();
$results[] = $indicatorEquityBetweenHomeAndAway->getResult();
$results[] = $indicatorMatchGenerationCriticity->getResult();
$results[] = $indicatorErrorInEmails->getResult();
$results[] = $indicatorMatchesWithInvalidPlayers->getResult();
$results[] = $indicatorMatchesWithInvalidDate->getResult();
$results[] = $indicatorTeamManyMatchesSameDay->getResult();

$indicatorName = filter_input(INPUT_GET, 'indicator');
if (!$indicatorName) {
    echo json_encode(array('results' => array_filter($results)));
    exit();
}
foreach ($results as $result) {
    if ($result['fieldLabel'] === $indicatorName) {
        try {
            echo generateCsv($result['details']);
        } catch (Exception $e) {
        }
        exit();
    }
}
