<?php

require_once __DIR__ . '/db_inc.php';
if (!isset($_SESSION)) {
    session_start();
}

function accentedToNonAccented($str)
{
    $unwanted_array = array('?' => 'S', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
        'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
        'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y',
        '-' => '', ' ' => '');
    return strtr($str, $unwanted_array);
}

function randomPassword()
{
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

function isUserExists($login)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM comptes_acces WHERE login = '$login'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

/**
 * @param $login
 * @param $email
 * @param $idTeam
 * @throws Exception
 */
function createUser($login, $email, $idTeam)
{
    global $db;
    conn_db();
    if (isUserExists($login)) {
        throw new Exception("Account already exists ! !");
    }
    if ($idTeam === NULL) {
        $idTeam = 0;
    }
    $password = randomPassword();
    $sql = "INSERT comptes_acces SET id_equipe = $idTeam, login = '$login', email = '$email', password = '$password'";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        throw new Exception("Unable to create new account in database !");
    }
    addActivity("Creation du compte $login pour l'equipe " . getTeamName($idTeam));
    require_once __DIR__ . '/../classes/Emails.php';
    $emailManager = new Emails();
    $emailManager->sendMailNewUser($email, $login, $password, $idTeam);
}

function deleteUsers($ids)
{
    $explodedIds = explode(',', $ids);
    $logins = array();
    foreach ($explodedIds as $id) {
        $logins[] = getUserLogin($id);
    }
    global $db;
    conn_db();
    $sql = "DELETE FROM comptes_acces WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    foreach ($logins as $login) {
        addActivity("Suppression du compte : $login");
    }
    return true;
}

function deleteGymnasiums($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM gymnase WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deleteClubs($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM clubs WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deleteTeams($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM equipes WHERE id_equipe IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

/**
 * @param $ids
 * @throws Exception
 */
function deleteMatches($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM matches WHERE id_match IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        $message = mysqli_error($db);
        throw new Exception($message);
    }
}

function deleteRanks($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM classements WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

/**
 * @param $ids
 * @throws Exception
 */
function deleteDays($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM journees WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        $message = mysqli_error($db);
        throw new Exception($message);
    }
}

function deleteLimitDates($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM dates_limite WHERE id_date IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deleteHallOfFame($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM hall_of_fame WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deleteTimeslot($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM creneau WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deleteCompetition($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM competitions WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deleteBlacklistGymnase($ids)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM blacklist_gymnase WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

/**
 * @param $ids
 * @throws Exception
 */
function deletePlayers($ids)
{
    $explodedIds = explode(',', $ids);
    $playersFullNames = array();
    foreach ($explodedIds as $id) {
        $playersFullNames[] = getPlayerFullName($id);
    }
    global $db;
    conn_db();
    $sql = "DELETE FROM joueurs WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    foreach ($playersFullNames as $playerFullName) {
        addActivity("Suppression du joueur : $playerFullName");
    }
}

function logout()
{
    session_destroy();
    die('<META HTTP-equiv="refresh" content=0;URL=' . filter_input(INPUT_SERVER, 'HTTP_REFERER') . '>');
}

function login()
{
    global $db;
    conn_db();
    $login = filter_input(INPUT_POST, 'login');
    $password = filter_input(INPUT_POST, 'password');
    if (($login === NULL) || ($password === NULL)) {
        disconn_db();
        echo json_encode(array(
            'success' => false,
            'message' => 'Veuillez remplir les champs de connexion'
        ));
        return;
    }
    $password = addslashes($password);
    $sql = "SELECT ca.id_equipe, ca.login, ca.password, ca.id AS id_user, p.name AS profile_name FROM comptes_acces ca
        LEFT JOIN users_profiles up ON up.user_id=ca.id
        LEFT JOIN profiles p ON p.id=up.profile_id
        WHERE ca.login = '$login' LIMIT 1";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    if (mysqli_num_rows($req) <= 0) {
        disconn_db();
        echo json_encode(array(
            'success' => false,
            'message' => 'Login incorrect'
        ));
        return;
    }
    $data = mysqli_fetch_assoc($req);
    if ($data['password'] != $password) {
        disconn_db();
        echo json_encode(array(
            'success' => false,
            'message' => 'Mot de passe invalide'
        ));
        return;
    }
    $_SESSION['id_equipe'] = $data['id_equipe'];
    $_SESSION['login'] = $data['login'];
    $_SESSION['password'] = $data['password'];
    $_SESSION['id_user'] = $data['id_user'];
    $_SESSION['profile_name'] = $data['profile_name'];
    disconn_db();
    header("Location: " . $_SERVER['HTTP_REFERER']);
    return;
}

function getQuickDetails($idEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        e.code_competition, 
        comp.libelle AS libelle_competition, 
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR '\n') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        WHERE e.id_equipe=$idEquipe        
        GROUP BY id_equipe
        ORDER BY comp.libelle, c.nom, nom_equipe ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(
        array(
            'success' => true,
            'data' => $results[0]
        )
    );
}

function getTournaments()
{
    global $db;
    conn_db();
    $sql = "SELECT c.id, c.code_competition, c.libelle 
        FROM competitions c 
        WHERE c.code_competition IN (SELECT DISTINCT code_competition FROM classements) 
        ORDER BY c.libelle ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getTeams()
{
    global $db;
    conn_db();
    $sql = "SELECT 
        e.code_competition, 
        comp.libelle AS libelle_competition, 
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase        
        GROUP BY team_full_name
        ORDER BY comp.libelle, c.nom, nom_equipe ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getTeam($id)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        e.code_competition, 
        comp.libelle AS libelle_competition, 
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        TO_BASE64(CONCAT(jresp.prenom, ' ', jresp.nom)) AS responsable_base64,
        TO_BASE64(jresp.telephone) AS telephone_1_base64,
        TO_BASE64(jsupp.telephone) AS telephone_2_base64,
        TO_BASE64(jresp.email) AS email_base64,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        WHERE e.id_equipe = $id
        GROUP BY team_full_name
        ORDER BY comp.libelle, c.nom, nom_equipe ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results[0]);
}

function getRankTeams()
{
    global $db;
    conn_db();
    $sql = "SELECT 
        cl.code_competition, 
        cl.division,
        comp.libelle AS libelle_competition,
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=cl.code_competition
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        GROUP BY team_full_name
        ORDER BY comp.libelle, c.nom, nom_equipe ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getWebSites()
{
    global $db;
    conn_db();
    $sql = "SELECT DISTINCT c.nom AS nom_club, e.web_site 
        FROM equipes e
        JOIN clubs c ON c.id = e.id_club
        WHERE web_site != ''
        ORDER BY c.nom ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getLastResults()
{
    global $db;
    conn_db();
    $sql = "SELECT DISTINCT 
    c.libelle AS competition, 
    IF(c.code_competition='f' OR c.code_competition='m' OR c.code_competition='mo', CONCAT('Division ', m.division, ' - ', j.nommage), CONCAT('Poule ', m.division, ' - ', j.nommage)) AS division_journee, 
    c.code_competition AS code_competition,
    m.division AS division,
    e1.id_equipe AS id_dom,
    e1.nom_equipe AS equipe_domicile,
    m.score_equipe_dom+0 AS score_equipe_dom, 
    m.score_equipe_ext+0 AS score_equipe_ext, 
    e2.id_equipe AS id_ext,
    e2.nom_equipe AS equipe_exterieur, 
    CONCAT(m.set_1_dom, '-', set_1_ext) AS set1, 
    CONCAT(m.set_2_dom, '-', set_2_ext) AS set2, 
    CONCAT(m.set_3_dom, '-', set_3_ext) AS set3, 
    CONCAT(m.set_4_dom, '-', set_4_ext) AS set4, 
    CONCAT(m.set_5_dom, '-', set_5_ext) AS set5, 
    m.date_reception
    FROM matches m
    LEFT JOIN activity a_modif ON (a_modif.comment LIKE 'Le match % a ete modifie' AND SPLIT_STRING(a_modif.comment, ' ', 3) = m.code_match)
    LEFT JOIN activity a_sheet_received ON (a_sheet_received.comment LIKE 'La feuille du match % a ete reçue' AND SPLIT_STRING(a_sheet_received.comment, ' ', 5) = m.code_match)
    JOIN journees j ON j.id=m.id_journee
    JOIN competitions c ON c.code_competition =  m.code_competition
    JOIN equipes e1 ON e1.id_equipe =  m.id_equipe_dom
    JOIN equipes e2 ON e2.id_equipe =  m.id_equipe_ext
    WHERE (
    (m.score_equipe_dom!=0 OR m.score_equipe_ext!=0)
    AND (m.date_reception <= CURDATE())
    AND (m.date_reception >= DATE_ADD(CURDATE(), INTERVAL -10 DAY) )
    AND (a_modif.activity_date >= m.date_reception OR a_sheet_received.activity_date >= m.date_reception)
    )
    ORDER BY c.libelle ASC, m.division ASC, j.nommage ASC, m.date_reception DESC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $code_competition = $data['code_competition'];
        switch ($code_competition) {
            case 'mo':
            case 'm':
            case 'f':
            case 'kh':
            case 'c':
            case 'po':
            case 'px':
                $division = $data['division'];
                $data['url'] = "championship.php?d=$division&c=$code_competition";
                $data['rang_dom'] = getTeamRank($data['code_competition'], $data['division'], $data['id_dom']);
                $data['rang_ext'] = getTeamRank($data['code_competition'], $data['division'], $data['id_ext']);
                break;
            case 'kf':
            case 'cf':
                $data['url'] = "cup.php?c=$code_competition";
                break;
            default :
                break;
        }
        $results[] = $data;
    }
    return json_encode($results);
}

/**
 * @param $team_id
 * @param $match_code
 * @throws Exception
 */
function check_team_allowed_to_ask_report($team_id, $match_code)
{
    require_once __DIR__ . '/../classes/MatchManager.php';
    $match_manager = new MatchManager();
    $matches = $match_manager->getMatches("m.code_match = '$match_code'");
    $this_match = $matches[0];
    $code_competition = $this_match['code_competition'];
    global $db;
    conn_db();
    $sql = "SELECT report_count 
            FROM classements 
            WHERE id_equipe = $team_id 
            AND code_competition = '$code_competition'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['report_count']) > 0) {
        throw new Exception("Demande refusée. Votre équipe a déjà demandé un report pour cette compétition.");
    }
}

/**
 * @param $code_match
 * @param $reason
 * @return bool
 * @throws Exception
 */
function askForReport($code_match, $reason)
{
    global $db;
    conn_db();
    $sessionIdEquipe = $_SESSION['id_equipe'];
    check_team_allowed_to_ask_report($sessionIdEquipe, $code_match);
    if (isTeamDomForMatch($sessionIdEquipe, $code_match)) {
        $sql = "UPDATE matches SET report_status = 'ASKED_BY_DOM' WHERE code_match = '$code_match'";
    } else {
        $sql = "UPDATE matches SET report_status = 'ASKED_BY_EXT' WHERE code_match = '$code_match'";
    }
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    addActivity("Report demandé par " . getTeamName($sessionIdEquipe) . " pour le match $code_match");
    require_once __DIR__ . '/../classes/Emails.php';
    $emailManager = new Emails();
    $emailManager->sendMailAskForReport($code_match, $reason, $sessionIdEquipe);
    return true;
}

/**
 * @param $code_match
 * @param $report_date
 * @return bool
 * @throws Exception
 */
function giveReportDate($code_match, $report_date)
{
    global $db;
    conn_db();
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "UPDATE matches SET date_reception = DATE(STR_TO_DATE('$report_date', '%Y-%m-%d')) WHERE code_match = '$code_match'";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    addActivity("Date de report transmise par " . getTeamName($sessionIdEquipe) . " pour le match $code_match");
    require_once __DIR__ . '/../classes/Emails.php';
    $emailManager = new Emails();
    $emailManager->sendMailGiveReportDate($code_match, $report_date, $sessionIdEquipe);
    return true;
}

/**
 * @param $code_match
 * @param $reason
 * @return bool
 * @throws Exception
 * @throws phpmailerException
 */
function refuseReport($code_match, $reason)
{
    global $db;
    conn_db();
    if (isTeamLeader()) {
        $sessionIdEquipe = $_SESSION['id_equipe'];
        if (isTeamDomForMatch($sessionIdEquipe, $code_match)) {
            $sql = "UPDATE matches SET report_status = 'REFUSED_BY_DOM' WHERE code_match = '$code_match'";
        } else {
            $sql = "UPDATE matches SET report_status = 'REFUSED_BY_EXT' WHERE code_match = '$code_match'";
        }
        $req = mysqli_query($db, $sql);
        disconn_db();
        if ($req === FALSE) {
            return false;
        }
        addActivity("Report refusé par " . getTeamName($sessionIdEquipe) . " pour le match $code_match");
        require_once __DIR__ . '/../classes/Emails.php';
        $emailManager = new Emails();
        $emailManager->sendMailRefuseReport($code_match, $reason, $sessionIdEquipe);
    }
    if (isAdmin()) {
        $sql = "UPDATE matches SET report_status = 'REFUSED_BY_ADMIN' WHERE code_match = '$code_match'";
        $req = mysqli_query($db, $sql);
        disconn_db();
        if ($req === FALSE) {
            return false;
        }
        addActivity("Report refusé par la commission pour le match $code_match");
        require_once __DIR__ . '/../classes/Emails.php';
        $emailManager = new Emails();
        $emailManager->sendMailRefuseReportAdmin($code_match);
    }
    return true;
}

/**
 * @param $code_match
 * @return bool
 * @throws Exception
 * @throws phpmailerException
 */
function acceptReport($code_match)
{
    global $db;
    conn_db();
    if (isTeamLeader()) {
        $sessionIdEquipe = $_SESSION['id_equipe'];
        if (isTeamDomForMatch($sessionIdEquipe, $code_match)) {
            $sql = "UPDATE matches SET report_status = 'ACCEPTED_BY_DOM' WHERE code_match = '$code_match'";
        } else {
            $sql = "UPDATE matches SET report_status = 'ACCEPTED_BY_EXT' WHERE code_match = '$code_match'";
        }
        $req = mysqli_query($db, $sql);
        disconn_db();
        if ($req === FALSE) {
            return false;
        }
        require_once __DIR__ . '/../classes/MatchManager.php';
        $match_manager = new MatchManager();
        $matches = $match_manager->getMatches("m.code_match = '$code_match'");
        $this_match = $matches[0];
        if ($sessionIdEquipe == $this_match['id_equipe_dom']) {
            incrementReportCount($this_match['code_competition'], $this_match['id_equipe_ext']);
        } else {
            incrementReportCount($this_match['code_competition'], $this_match['id_equipe_dom']);
        }
        addActivity("Report accepté par " . getTeamName($sessionIdEquipe) . " pour le match $code_match");
        require_once __DIR__ . '/../classes/Emails.php';
        $emailManager = new Emails();
        $emailManager->sendMailAcceptReport($code_match, $sessionIdEquipe);
    }
    if (isAdmin()) {
        $sql = "UPDATE matches SET report_status = 'REFUSED_BY_ADMIN' WHERE code_match = '$code_match'";
        $req = mysqli_query($db, $sql);
        disconn_db();
        if ($req === FALSE) {
            return false;
        }
        addActivity("Report refusé par la commission pour le match $code_match");
        require_once __DIR__ . '/../classes/Emails.php';
        $emailManager = new Emails();
        $emailManager->sendMailRefuseReportAdmin($code_match);
    }
    return true;
}

function isTeamDomForMatch($id_team, $code_match)
{
    global $db;
    conn_db();
    $sql = "SELECT * FROM matches 
        WHERE id_equipe_dom=$id_team 
        AND code_match='$code_match'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return count($results) > 0;
}

function isAdmin()
{
    return (isset($_SESSION['profile_name']) && $_SESSION['profile_name'] == "ADMINISTRATEUR");
}

function isTeamSheetAllowedForUser($idTeam)
{
    if (isAdmin()) {
        return true;
    }
    if (!isTeamLeader()) {
        return false;
    }
    return isSameRankingTable($idTeam);
}

function isTeamLeader()
{
    return (isset($_SESSION['profile_name']) && $_SESSION['profile_name'] == "RESPONSABLE_EQUIPE");
}

function isSameRankingTable($id_equipe)
{
    global $db;
    $sessionIdEquipe = $_SESSION['id_equipe'];
    if ($sessionIdEquipe === $id_equipe) {
        return true;
    }
    conn_db();
    $sql = "SELECT * FROM matches 
        WHERE id_equipe_dom=$sessionIdEquipe 
        OR id_equipe_ext=$sessionIdEquipe";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $result) {
        if ($result['id_equipe_dom'] === $id_equipe) {
            return true;
        }
        if ($result['id_equipe_ext'] === $id_equipe) {
            return true;
        }
    }
    return false;
}

function getTeamEmail($id)
{
    global $db;
    conn_db();
    $sql = "SELECT j.email 
        FROM joueurs j 
        JOIN joueur_equipe je ON 
                                je.id_equipe = $id 
                                AND je.id_joueur = j.id 
                                AND je.is_leader+0 > 0";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_assoc($req)) {
        return $data['email'];
    }
    return null;
}

function getLimitDate($compet)
{
    global $db;
    conn_db();
    $sql = "SELECT date_limite FROM dates_limite WHERE code_competition = '$compet'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_assoc($req)) {
        echo $data['date_limite'];
    }
}

function getParentCompetition($compet)
{
    global $db;
    conn_db();
    $sql = "SELECT id_compet_maitre FROM competitions WHERE code_competition = '$compet'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_assoc($req)) {
        return $data['id_compet_maitre'];
    }
    return null;
}

function getPlayersFromTeam($id_equipe)
{
    global $db;
    conn_db();
    $sql = "SELECT
        CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS full_name,
        j.prenom, 
        j.nom, 
        j.telephone, 
        j.email, 
        j.num_licence, 
        p.path_photo,
        j.sexe, 
        j.departement_affiliation, 
        j.est_actif+0 AS est_actif, 
        j.id_club, 
        j.telephone2, 
        j.email2, 
        j.est_responsable_club+0 AS est_responsable_club, 
        je.is_captain+0 AS is_captain, 
        je.is_vice_leader+0 AS is_vice_leader, 
        je.is_leader+0 AS is_leader, 
        j.id, 
        j.show_photo+0 AS show_photo,
        DATE_FORMAT(j.date_homologation, '%d/%m/%Y') AS date_homologation 
        FROM joueur_equipe je
        LEFT JOIN joueurs j ON j.id=je.id_joueur
        LEFT JOIN photos p ON p.id = j.id_photo
    WHERE id_equipe = $id_equipe";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $index => $result) {
        if ($result['show_photo'] === '1') {
            $results[$index]['path_photo'] = accentedToNonAccented($result['path_photo']);
            if (($results[$index]['path_photo'] == '') || (file_exists("../" . $results[$index]['path_photo']) === FALSE)) {
                switch ($result['sexe']) {
                    case 'M':
                        $results[$index]['path_photo'] = 'images/MaleMissingPhoto.png';
                        break;
                    case 'F':
                        $results[$index]['path_photo'] = 'images/FemaleMissingPhoto.png';
                        break;
                    default:
                        break;
                }
            }
        } else {
            switch ($result['sexe']) {
                case 'M':
                    $results[$index]['path_photo'] = 'images/MalePhotoNotAllowed.png';
                    break;
                case 'F':
                    $results[$index]['path_photo'] = 'images/FemalePhotoNotAllowed.png';
                    break;
                default:
                    break;
            }
        }
    }
    return json_encode($results);
}

function getConnectedUser()
{
    if (isAdmin()) {
        return "Administrateur";
    }
    if (isTeamLeader()) {
        $jsonTeamDetails = json_decode(getMyTeam());
        return $jsonTeamDetails[0]->team_full_name;
    }
    if (isset($_SESSION['login'])) {
        return $_SESSION['login'];
    }
    return "";
}

function getIdsTeamRequestingNextMatches()
{
    global $db;
    conn_db();
    $sql = "SELECT REPLACE(REPLACE(registry_key, '.is_remind_matches',''), 'users.','') AS user_id FROM registry WHERE registry_key LIKE 'users.%.is_remind_matches' AND registry_value = 'on'";
    $req = mysqli_query($db, $sql);
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return $results;
}

function createCsvString($data)
{
    if (!$fp = fopen('php://temp', 'w+')) {
        return FALSE;
    }
    $isHeaderWritten = false;
    foreach ($data as $line) {
        if (!$isHeaderWritten) {
            fputcsv($fp, array_keys($line));
            $isHeaderWritten = true;
        }
        fputcsv($fp, $line);
    }
    rewind($fp);
    return stream_get_contents($fp);
}

function getTeamsEmailsFromMatchReport($code_match)
{
    global $db;
    conn_db();
    $sql = "SELECT
      m.id_equipe_dom,
      m.id_equipe_ext,
      m.code_competition
      FROM matches m
      WHERE m.code_match = '$code_match'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $data = mysqli_fetch_assoc($req);
    $emailDom = getTeamEmail($data['id_equipe_dom']);
    $emailExt = getTeamEmail($data['id_equipe_ext']);
    $emailReport = '';
    switch ($data['code_competition']) {
        case 'm':
            $emailReport = 'report-6x6-mmx@ufolep13volley.org';
            break;
        case 'f':
            $emailReport = 'report-4x4-fem@ufolep13volley.org';
            break;
        case 'mo':
            $emailReport = 'report-4x4-mxt@ufolep13volley.org';
            break;
        case 'kh':
            $emailReport = 'report-4x4-ckh@ufolep13volley.org';
            break;
    }
    return array($emailDom, $emailExt, $emailReport);
}

function getTeamsEmailsFromMatch($code_match)
{
    global $db;
    conn_db();
    $sql = "SELECT
      m.id_equipe_dom,
      m.id_equipe_ext,
      m.code_competition,
      LEFT(m.division, 1) AS division
      FROM matches m
      WHERE m.code_match = '$code_match'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $data = mysqli_fetch_assoc($req);
    $emailDom = getTeamEmail($data['id_equipe_dom']);
    $emailExt = getTeamEmail($data['id_equipe_ext']);
    $emailCtsd = '';
    $division = $data['division'];
    switch ($data['code_competition']) {
        case 'm':
            $emailCtsd = 'd' . $division . 'm-6x6@ufolep13volley.org';
            break;
        case 'f':
            $emailCtsd = 'd' . $division . 'f-4x4@ufolep13volley.org';
            break;
        case 'mo':
            $emailCtsd = 'd' . $division . 'mi-4x4@ufolep13volley.org';
            break;
        case 'kh':
        case 'kf':
            $emailCtsd = 'khanna@ufolep13volley.org';
            break;
        case 'c':
        case 'cf':
            $emailCtsd = 'isoardi@ufolep13volley.org';
            break;
    }
    return array($emailDom, $emailExt, $emailCtsd);
}

function getRank($compet, $div)
{
    global $db;
    conn_db();
    $sql = "SELECT
  @r := @r + 1 AS rang,
  z.*
FROM (
       SELECT
         e.id_equipe,
         '$compet' AS code_competition,
         e.nom_equipe                      AS equipe,
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3
           THEN 3
             ELSE 0 END) + SUM(CASE WHEN e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3
           THEN 3
                               ELSE 0 END) +
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3 AND m.forfait_dom = 0
           THEN 1
             ELSE 0 END) + SUM(CASE WHEN e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3 AND m.forfait_ext = 0
           THEN 1
                               ELSE 0 END)
         - c.penalite                      AS points,
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3
           THEN 1
             ELSE 0 END) + SUM(CASE WHEN e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3
           THEN 1
                               ELSE 0 END) +
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3
           THEN 1
             ELSE 0 END) + SUM(CASE WHEN e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3
           THEN 1
                               ELSE 0 END)                 AS joues,
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3
           THEN 1
             ELSE 0 END) + SUM(CASE WHEN e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3
           THEN 1
                               ELSE 0 END) AS gagnes,
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3
           THEN 1
             ELSE 0 END) + SUM(CASE WHEN e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3
           THEN 1
                               ELSE 0 END) AS perdus,
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom
           THEN m.score_equipe_dom
             ELSE m.score_equipe_ext END)  AS sets_pour,
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom
           THEN m.score_equipe_ext
             ELSE m.score_equipe_dom END)  AS sets_contre,
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom
           THEN m.score_equipe_dom
             ELSE m.score_equipe_ext END) - SUM(CASE WHEN e.id_equipe = m.id_equipe_dom
           THEN m.score_equipe_ext
                                                ELSE m.score_equipe_dom END)         AS diff,
         c.penalite                        AS penalites,
         SUM(CASE WHEN e.id_equipe = m.id_equipe_dom AND m.forfait_dom = 1
           THEN 1
             ELSE 0 END) + SUM(CASE WHEN e.id_equipe = m.id_equipe_ext AND m.forfait_ext = 1
           THEN 1
                               ELSE 0 END) AS matches_lost_by_forfeit_count,
          c.report_count
       FROM
         classements c
         JOIN equipes e ON e.id_equipe = c.id_equipe
         LEFT JOIN matches m ON m.code_competition = c.code_competition AND m.division = c.division AND (m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe)
       WHERE c.code_competition = '$compet' AND c.division = '$div'
       GROUP BY e.id_equipe
       ORDER BY points DESC, diff DESC
     ) z, (SELECT @r := 0) y";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getRanks()
{
    global $db;
    conn_db();
    $sql = "SELECT
      cl.id,
      cl.code_competition,
      co.libelle AS nom_competition,
      cl.division,
      cl.id_equipe,
      e.nom_equipe,
      cl.rank_start
      FROM classements cl
      JOIN competitions co ON co.code_competition = cl.code_competition
      JOIN equipes e ON e.id_equipe = cl.id_equipe";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getDivisions()
{
    global $db;
    conn_db();
    $sql = "SELECT
        DISTINCT c.division,
        c.code_competition
      FROM classements c";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getTeamRank($competition, $league, $idTeam)
{
    $results = json_decode(getRank($competition, $league), true);
    foreach ($results as $data) {
        if ($data['id_equipe'] === $idTeam) {
            return $data['rang'];
        }
    }
    return '';
}

function addPenalty($compet, $id_equipe)
{
    global $db;
    conn_db();
    $sql = "SELECT penalite,division FROM classements WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    if (mysqli_num_rows($req) !== 1) {
        return false;
    }
    $data = mysqli_fetch_assoc($req);
    $penalite = $data['penalite'];
    //$division = $data['division'];
    $penalite++;
    $sqlmaj = "UPDATE classements SET penalite = $penalite WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
    $req2 = mysqli_query($db, $sqlmaj);
    if ($req2 === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("Une penalite a ete infligee a l'equipe " . getTeamName($id_equipe));
    return true;
}

function removePenalty($compet, $id_equipe)
{
    global $db;
    conn_db();
    $sql = "SELECT penalite,division FROM classements WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    if (mysqli_num_rows($req) !== 1) {
        return false;
    }
    $data = mysqli_fetch_assoc($req);
    $penalite = $data['penalite'];
    //$division = $data['division'];
    $penalite--;
    if ($penalite < 0) {
        $penalite = 0;
    }
    $sqlmaj = "UPDATE classements SET penalite = $penalite WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
    $req2 = mysqli_query($db, $sqlmaj);
    if ($req2 === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("Une penalite a ete annulee pour l'equipe " . getTeamName($id_equipe));
    return true;
}

function incrementReportCount($compet, $id_equipe)
{
    global $db;
    conn_db();
    $sql = "UPDATE classements SET report_count = report_count + 1 WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("Un report a ete comptabilise pour l'equipe " . getTeamName($id_equipe));
    return true;
}

function decrementReportCount($compet, $id_equipe)
{
    global $db;
    conn_db();
    $sql = "UPDATE classements SET report_count = report_count - 1 WHERE id_equipe = $id_equipe AND code_competition = '$compet' AND report_count > 0";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("Un report a ete retire pour l'equipe " . getTeamName($id_equipe));
    return true;
}

function removeTeamFromCompetition($compet, $id_equipe)
{
    global $db;
    conn_db();
    $sql = "DELETE FROM classements 
      WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "DELETE FROM matches 
      WHERE (id_equipe_dom = $id_equipe AND code_competition = '$compet') 
      OR (id_equipe_ext = $id_equipe AND code_competition = '$compet')";
    $req2 = mysqli_query($db, $sql);
    if ($req2 === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("L'equipe " . getTeamName($id_equipe) . " a ete supprimee de la competition " . getTournamentName($compet));
    return true;
}

function certifyMatch($code_match)
{
    global $db;
    conn_db();
    $sql = "UPDATE matches SET certif = 1 WHERE code_match = '$code_match'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("Le match $code_match a ete certifie");
    return true;
}

function invalidateMatch($code_match)
{
    global $db;
    conn_db();
    $sql = "UPDATE matches SET certif = 0 WHERE code_match = '$code_match'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("La certification du match $code_match a ete annulee");
    return true;
}

function addActivity($comment)
{
    global $db;
    conn_db();
    if (!empty($_SESSION['id_user'])) {
        $sessionIdUser = $_SESSION['id_user'];
        $sql = "INSERT activity SET comment=\"$comment\", activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s'), user_id=$sessionIdUser";
    } else {
        $sql = "INSERT activity SET comment=\"$comment\", activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s')";
    }
    mysqli_query($db, $sql);
    disconn_db();
    return;
}

function getDays()
{
    global $db;
    conn_db();
    $sql = "SELECT
      j.id,
      j.code_competition,
      c.libelle                                                                  AS libelle_competition,
      j.numero,
      j.nommage,
      CONCAT('Semaine du ', 
        DATE_FORMAT(j.start_date, '%W %d %M'), 
        ' au ',
        DATE_FORMAT(ADDDATE(j.start_date, INTERVAL 4 DAY), '%W %d %M %Y')) AS libelle,
      DATE_FORMAT(j.start_date, '%d/%m/%Y') AS start_date
    FROM journees j
      JOIN competitions c ON c.code_competition = j.code_competition";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getLimitDates()
{
    global $db;
    conn_db();
    $sql = "SELECT
        d.id_date,
        d.code_competition,
        c.libelle AS libelle_competition,
        d.date_limite
        FROM dates_limite d
        JOIN competitions c ON c.code_competition = d.code_competition";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getMyTeam()
{
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT 
        e.code_competition, 
        comp.libelle AS libelle_competition, 
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        WHERE e.id_equipe=$sessionIdEquipe        
        GROUP BY team_full_name
        ORDER BY comp.libelle, c.nom, nom_equipe ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getPlayersPdf($idTeam, $rootPath = '../', $doHideInactivePlayers = false)
{
    if ($idTeam === NULL) {
        return false;
    }
    global $db;
    conn_db();
    if (!isTeamSheetAllowedForUser($idTeam)) {
        return false;
    }
    $sql = "SELECT 
        CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS full_name, 
        CONCAT(UPPER(LEFT(j.prenom, 1)), LOWER(SUBSTRING(j.prenom, 2))) AS prenom, 
        UPPER(j.nom) AS nom, 
        j.telephone, 
        j.email, 
        j.num_licence, 
        CONCAT(LPAD(j.departement_affiliation, 3, '0'), j.num_licence) AS num_licence_ext, 
        p.path_photo,
        j.sexe, 
        j.departement_affiliation, 
        j.est_actif+0 AS est_actif, 
        j.id_club, 
        j.telephone2, 
        j.email2, 
        j.est_responsable_club+0 AS est_responsable_club, 
        je.is_captain+0 AS is_captain, 
        je.is_vice_leader+0 AS is_vice_leader, 
        je.is_leader+0 AS is_leader, 
        j.id, 
        j.show_photo+0 AS show_photo,
        DATE_FORMAT(j.date_homologation, '%d/%m/%Y') AS date_homologation 
        FROM joueur_equipe je
        LEFT JOIN joueurs j ON j.id=je.id_joueur
        LEFT JOIN photos p ON p.id = j.id_photo
        WHERE 
        je.id_equipe = $idTeam";
    if ($doHideInactivePlayers) {
        $sql .= " AND j.est_actif+0=1 ";
    }
    $sql .= " ORDER BY sexe, nom ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $index => $result) {
        if ($result['show_photo'] === '1') {
            $results[$index]['path_photo'] = accentedToNonAccented($result['path_photo']);
            if (($results[$index]['path_photo'] == '') || (file_exists($rootPath . $results[$index]['path_photo']) === FALSE)) {
                switch ($result['sexe']) {
                    case 'M':
                        $results[$index]['path_photo'] = 'images/MaleMissingPhoto.png';
                        break;
                    case 'F':
                        $results[$index]['path_photo'] = 'images/FemaleMissingPhoto.png';
                        break;
                    default:
                        break;
                }
            }
        } else {
            switch ($result['sexe']) {
                case 'M':
                    $results[$index]['path_photo'] = 'images/MalePhotoNotAllowed.png';
                    break;
                case 'F':
                    $results[$index]['path_photo'] = 'images/FemalePhotoNotAllowed.png';
                    break;
                default:
                    break;
            }
        }
    }
    return json_encode($results);
}

function getPlayers()
{
    global $db;
    conn_db();
    // TODO Filter teams in competition.
    // TODO  AND e.id_equipe IN (SELECT id_equipe FROM classements) pas nécessaire a priori...
    $sql = "SELECT
    CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS full_name,
    j.prenom, 
    j.nom, 
    j.telephone, 
    j.email, 
    j.num_licence,
    p.path_photo,
    j.sexe, 
    j.departement_affiliation, 
    j.est_actif+0 AS est_actif, 
    j.id_club, 
    c.nom AS club, 
    j.telephone2, 
    j.email2, 
    j.est_responsable_club+0 AS est_responsable_club, 
    j.show_photo+0 AS show_photo,
    j.id, 
    GROUP_CONCAT( CONCAT(e.nom_equipe, '(',e.code_competition,')') SEPARATOR ', ') AS teams_list,
    DATE_FORMAT(j.date_homologation, '%d/%m/%Y') AS date_homologation
FROM joueurs j 
LEFT JOIN joueur_equipe je ON je.id_joueur = j.id
LEFT JOIN equipes e ON e.id_equipe=je.id_equipe AND e.id_equipe IN (SELECT id_equipe FROM classements)
LEFT JOIN clubs c ON c.id = j.id_club
LEFT JOIN photos p ON p.id = j.id_photo
GROUP BY j.id";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $data['path_photo'] = accentedToNonAccented($data['path_photo']);
        $results[] = $data;

    }
    return json_encode($results);
}

function getProfiles()
{
    global $db;
    conn_db();
    $sql = "SELECT id, name FROM profiles";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function isPlayerInTeam($idPlayer, $idTeam)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_joueur = $idPlayer AND id_equipe = $idTeam";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function updateMyTeamCaptain($idPlayer)
{
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (!isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_captain = 0 WHERE id_equipe = $idTeam";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_captain = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau capitaine : " . getPlayerFullName($idPlayer));
    return true;
}

function updateMyTeamViceLeader($idPlayer)
{
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (!isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_vice_leader = 0 WHERE id_equipe = $idTeam";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_vice_leader = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau suppleant : " . getPlayerFullName($idPlayer));
    return true;
}

function updateMyTeamLeader($idPlayer)
{
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (!isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_leader = 0 WHERE id_equipe = $idTeam";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_leader = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau responsable : " . getPlayerFullName($idPlayer));
    return true;
}

function addPlayerToMyTeam($idPlayer)
{
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (addPlayerToTeam($idPlayer, $idTeam) === false) {
        return false;
    }
    $idClubPlayer = getPlayersIdClub($idPlayer);
    if ($idClubPlayer === '0') {
        $idClubMyTeam = getMyTeamIdClub();
        if (addPlayersToClub($idPlayer, $idClubMyTeam) === false) {
            return false;
        }
    }
    return true;
}

function getMyTeamIdClub()
{
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT 
        e.id_club
        FROM equipes e
        WHERE e.id_equipe=$sessionIdEquipe";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return $results[0]['id_club'];
}

function getPlayersIdClub($idPlayer)
{
    global $db;
    conn_db();
    $sql = "SELECT j.id_club
        FROM joueurs j
        WHERE j.id = $idPlayer";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    disconn_db();
    return $results[0]['id_club'];
}

function addPlayerToTeam($idPlayer, $idTeam)
{
    global $db;
    conn_db();
    if (isPlayerInTeam($idPlayer, $idTeam)) {
        return true;
    }
    $sql = "INSERT joueur_equipe SET id_joueur = $idPlayer, id_equipe = $idTeam";
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    addActivity("Ajout de " . getPlayerFullName($idPlayer) . " a l'equipe " . getTeamName($idTeam));
    return true;
}

function getPlayerFullName($idPlayer)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS player_full_name
        FROM joueurs j
        WHERE j.id = $idPlayer";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    disconn_db();
    return $results[0]['player_full_name'];
}

function getUserLogin($idUser)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        ca.login AS login
        FROM comptes_acces ca
        WHERE ca.id = $idUser";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    disconn_db();
    return $results[0]['login'];
}

function getTeamName($idTeam)
{
    global $db;
    if ($idTeam === 0) {
        return 'Non renseigné';
    }
    conn_db();
    $sql = "SELECT 
        CONCAT(e.nom_equipe, '(',e.code_competition,')') AS team_name 
        FROM equipes e 
        WHERE e.id_equipe = $idTeam";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    disconn_db();
    return $results[0]['team_name'];
}

function getTournamentName($tournamentCode)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        c.libelle AS tournament_name
        FROM competitions c 
        WHERE c.code_competition = '$tournamentCode'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    disconn_db();
    return $results[0]['tournament_name'];
}

function getClubName($idClub)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        c.nom AS club_name 
        FROM clubs c 
        WHERE c.id = $idClub";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    disconn_db();
    return $results[0]['club_name'];
}

function getProfileName($idProfile)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        p.name AS profile_name 
        FROM profiles p 
        WHERE p.id = $idProfile";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    disconn_db();
    return $results[0]['profile_name'];
}

function addPlayersToClub($idPlayers, $idClub)
{
    global $db;
    conn_db();
    if (!isAdmin()) {
        if (!isTeamLeader()) {
            return false;
        }
    }
    $sql = "UPDATE joueurs SET id_club = $idClub WHERE id IN ($idPlayers)";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    foreach (explode(',', $idPlayers) as $idPlayer) {
        addActivity(getPlayerFullName($idPlayer) . " a ete ajoute au club " . getClubName($idClub));
    }
    return true;
}

function hasProfile($idUser)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM users_profiles WHERE user_id = $idUser";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function addProfileToUsers($idProfile, $idUsers)
{
    global $db;
    foreach (explode(',', $idUsers) as $idUser) {
        $hasProfile = hasProfile($idUser);
        conn_db();
        if ($hasProfile) {
            $sql = "UPDATE ";
        } else {
            $sql = "INSERT ";
        }
        $sql .= "users_profiles SET profile_id = $idProfile, user_id = $idUser ";
        if ($hasProfile) {
            $sql .= "WHERE user_id = $idUser";
        }
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            return false;
        }
        disconn_db();
        addActivity(getUserLogin($idUser) . " a obtenu le profil " . getProfileName($idProfile));
    }
    return true;
}

function getIdClubFromIdTeam($idTeam)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        e.id_club
        FROM equipes e 
        WHERE e.id_equipe = $idTeam";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    disconn_db();
    return $results[0]['id_club'];
}

function addPlayersToTeam($idPlayers, $idTeam)
{
    if (!isAdmin()) {
        return false;
    }
    $idClub = getIdClubFromIdTeam($idTeam);
    if (!addPlayersToClub($idPlayers, $idClub)) {
        return false;
    }
    foreach (explode(',', $idPlayers) as $idPlayer) {
        if (!addPlayerToTeam($idPlayer, $idTeam)) {
            return false;
        }
    }
    return true;
}

function removePlayerFromMyTeam($idPlayer)
{
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (!isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "DELETE FROM joueur_equipe WHERE id_joueur = $idPlayer AND id_equipe = $idTeam";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity(getPlayerFullName($idPlayer) . " a ete supprime de l'equipe " . getTeamName($idTeam));
    return true;
}

function removeTimeSlot($id)
{
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $sql = "DELETE FROM creneau WHERE id = $id";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    addActivity("Un créneau a été supprimé");
    return true;
}

function getTeamSheet($idTeam)
{
    if ($idTeam === NULL) {
        return false;
    }
    global $db;
    conn_db();
    if (!isTeamSheetAllowedForUser($idTeam)) {
        return false;
    }
    $sql = "SELECT 
        c.nom AS club,
        e.code_competition, 
        comp.libelle AS championnat,
        cla.division,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS leader,
        jresp.telephone AS portable,
        jresp.email AS courriel,
        GROUP_CONCAT(CONCAT(LEFT(cr.jour, 2), ' ', cr.heure, ' ', g.nom) SEPARATOR '\n') AS gymnasiums_list,
        e.nom_equipe AS equipe,
        DATE_FORMAT(NOW(), '%d/%m/%Y') AS date_visa_ctsd
        FROM equipes e
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN classements cla ON cla.code_competition=e.code_competition AND cla.id_equipe=e.id_equipe
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        WHERE e.id_equipe = $idTeam
        GROUP BY equipe";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (count($results) === 0) {
        return false;
    }
    return json_encode($results);
}

/**
 * @param $uploadfile
 * @param $idPhoto
 * @throws Exception
 */
function insertPhoto($uploadfile, &$idPhoto)
{
    global $db;
    conn_db();
    $sql = "INSERT INTO photos SET path_photo = '$uploadfile'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    $idPhoto = mysqli_insert_id($db);
    disconn_db();
    return;
}

/**
 * @param $idPlayer
 * @param $idPhoto
 * @throws Exception
 */
function linkPlayerToPhoto($idPlayer, $idPhoto)
{
    global $db;
    conn_db();
    $sql = "UPDATE joueurs j SET j.id_photo = $idPhoto WHERE id = $idPlayer";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    return;
}

/**
 * @param $idTeam
 * @param $idPhoto
 * @throws Exception
 */
function linkTeamToPhoto($idTeam, $idPhoto)
{
    global $db;
    conn_db();
    $sql = "UPDATE equipes e SET e.id_photo = $idPhoto WHERE id_equipe = $idTeam";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    return;
}

/**
 * @param $inputs
 * @param int $newId
 * @throws Exception
 */
function savePhoto($inputs, $newId = 0)
{
    $lastName = $inputs['nom'];
    $firstName = $inputs['prenom'];
    if (empty($_FILES['photo']['name'])) {
        return;
    }
    $uploaddir = '../players_pics/';
    $iteration = 1;
    $uploadfile = "$uploaddir$lastName$firstName$iteration.jpg";
    while (file_exists($uploadfile)) {
        $iteration++;
        $uploadfile = "$uploaddir$lastName$firstName$iteration.jpg";
    }
    $idPhoto = 0;
    insertPhoto(substr($uploadfile, 3), $idPhoto);
    $idPlayer = $inputs['id'];
    if (empty($inputs['id'])) {
        $idPlayer = $newId;
    }
    linkPlayerToPhoto($idPlayer, $idPhoto);
    if (move_uploaded_file($_FILES['photo']['tmp_name'], accentedToNonAccented($uploadfile))) {
        addActivity("Une nouvelle photo a ete transmise pour le joueur $firstName $lastName");
    }
    return;
}

/**
 * @param $idTeam
 * @throws Exception
 */
function saveTeamPhoto($idTeam)
{
    $team = getTeam($idTeam);
    if (empty($_FILES['photo']['name'])) {
        return;
    }
    $uploaddir = '../teams_pics/';
    $uploadfile = "$uploaddir$idTeam.jpg";
    $idPhoto = 0;
    insertPhoto(substr($uploadfile, 3), $idPhoto);
    linkTeamToPhoto($idTeam, $idPhoto);
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadfile)) {
        addActivity("Une nouvelle photo a ete transmise pour l'équipe " . json_decode($team)->team_full_name);
    }
    return;
}

function isPlayerExists($licenceNumber)
{
    if ($licenceNumber === '') {
        return false;
    }
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueurs WHERE num_licence = '$licenceNumber'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function isProfileExists($name)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM profiles WHERE name = '$name'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

/**
 * @throws Exception
 */
function savePlayer()
{
    global $db;
    $inputs = filter_input_array(INPUT_POST);
    if (empty($inputs['id'])) {
        if (!empty($inputs['num_licence'])) {
            if (isPlayerExists($inputs['num_licence'])) {
                throw new Exception("Un joueur avec le même numéro de licence existe déjà !");
            }
        }
    }
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " joueurs SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
            case 'id_team':
            case 'dirtyFields':
                continue;
            case 'departement_affiliation':
            case 'id_club':
                $sql .= "$key = $value,";
                break;
            case 'date_homologation':
                $sql .= "$key = DATE(STR_TO_DATE('$value', '%d/%m/%Y')),";
                break;
            case 'est_actif':
            case 'est_responsable_club':
            case 'show_photo':
                $val = ($value === 'on') ? 1 : 0;
                $sql .= "$key = $val,";
                break;
            default:
                if (empty($inputs[$key]) || $inputs[$key] == 'null') {
                    $sql .= "$key = NULL,";
                } else {
                    $sql .= "$key = '$value',";
                }
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    $newId = mysqli_insert_id($db);
    disconn_db();
    if (empty($inputs['id'])) {
        if (isTeamLeader()) {
            if ($newId > 0) {
                if (!addPlayerToMyTeam($newId)) {
                    throw new Exception("Erreur durant l'ajout du joueur à l'équipe");
                }
            }
        }
    }
    if (empty($inputs['id'])) {
        $firstName = $inputs['prenom'];
        $name = $inputs['nom'];
        $comment = "Creation d'un nouveau joueur : $firstName $name";
        addActivity($comment);
    } else {
        $dirtyFields = filter_input(INPUT_POST, 'dirtyFields');
        if ($dirtyFields) {
            $fieldsArray = explode(',', $dirtyFields);
            foreach ($fieldsArray as $fieldName) {
                $fieldValue = filter_input(INPUT_POST, $fieldName);
                $firstName = $inputs['prenom'];
                $name = $inputs['nom'];
                $comment = "$firstName $name : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
                addActivity($comment);
            }
        }
    }
    savePhoto($inputs, $newId);
}

function saveTimeSlot()
{
    global $db;
    $inputs = array(
        'id' => filter_input(INPUT_POST, 'id'),
        'id_equipe' => isTeamLeader() ? $_SESSION['id_equipe'] : filter_input(INPUT_POST, 'id_equipe'),
        'id_gymnase' => filter_input(INPUT_POST, 'id_gymnase'),
        'jour' => filter_input(INPUT_POST, 'jour'),
        'heure' => filter_input(INPUT_POST, 'heure'),
        'has_time_constraint' => filter_input(INPUT_POST, 'has_time_constraint')
    );
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " creneau SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
                continue;
            case 'id_equipe':
            case 'id_gymnase':
                $sql .= "$key = $value,";
                break;
            case 'has_time_constraint':
                $val = ($value === 'on') ? 1 : 0;
                $sql .= "$key = $val,";
                break;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    disconn_db();
    $teamName = getTeamName($inputs['id_equipe']);
    if (empty($inputs['id'])) {
        $comment = "Creation d'un nouveau creneau pour l'équipe $teamName";
    } else {
        $comment = "Modification d'un creneau existant pour l'équipe $teamName";
    }
    addActivity($comment);
    return true;
}

function saveProfile()
{
    global $db;
    $inputs = array(
        'id' => filter_input(INPUT_POST, 'id'),
        'name' => filter_input(INPUT_POST, 'name')
    );
    if (empty($inputs['id'])) {
        if (isProfileExists($inputs['name'])) {
            return false;
        }
    }
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " profiles SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
                continue;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    if (empty($inputs['id'])) {
        $name = $inputs['name'];
        $comment = "Creation d'un nouveau profil : $name";
        addActivity($comment);
    } else {
        $dirtyFields = filter_input(INPUT_POST, 'dirtyFields');
        if ($dirtyFields) {
            $fieldsArray = explode(',', $dirtyFields);
            foreach ($fieldsArray as $fieldName) {
                $fieldValue = filter_input(INPUT_POST, $fieldName);
                $name = $inputs['name'];
                $comment = "$name : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
                addActivity($comment);
            }
        }
    }
    return true;
}

function getUsers()
{
    global $db;
    conn_db();
    $sql = "SELECT ca.id, ca.login, ca.password, ca.email, e.id_equipe AS id_team, e.nom_equipe AS team_name, c.nom AS club_name, up.profile_id AS id_profile, p.name AS profile
        FROM comptes_acces ca 
        LEFT JOIN equipes e ON e.id_equipe=ca.id_equipe 
        LEFT JOIN clubs c ON c.id=e.id_club 
        LEFT JOIN users_profiles up ON up.user_id=ca.id 
        LEFT JOIN profiles p ON p.id=up.profile_id";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getWeekSchedule()
{
    global $db;
    conn_db();
    $sql = "SELECT 
        CONCAT(g.ville, ' - ', g.nom) AS gymnasium,
        c.jour AS dayOfWeek,
        c.heure AS startTime,
        CONCAT(e.nom_equipe, ' - ', comp.libelle) AS team
        FROM creneau c
        JOIN gymnase g ON g.id = c.id_gymnase
        JOIN equipes e ON e.id_equipe = c.id_equipe
        JOIN clubs cl ON cl.id = e.id_club
        JOIN competitions comp ON comp.code_competition = e.code_competition
        ORDER BY dayOfWeek, startTime, gymnasium";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getHallOfFame()
{
    global $db;
    conn_db();
    $sql = "SELECT 
        id, 
        title, 
        team_name,
        league,
        period
        FROM hall_of_fame
        ORDER BY period";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getHallOfFameDisplay()
{
    global $db;
    conn_db();
    $sql = "SELECT
      hof.period,
      CASE WHEN hof.title LIKE '%Division%'
        THEN SUBSTRING_INDEX(hof.title, 'Division ', -1)
      ELSE '' END                 AS division,
      CASE WHEN hof.title LIKE '%mi-saison%'
        THEN 1
      ELSE 2 END                  AS demi_saison,
      hof_champion.team_name      AS champion,
      hof_vice_champion.team_name AS vice_champion,
      hof.league
FROM hall_of_fame hof
  JOIN hall_of_fame hof_champion ON
                                   hof_champion.league = hof.league AND
                                   hof_champion.period = hof.period AND
                                   (CASE WHEN hof_champion.title LIKE '%Division%'
                                     THEN SUBSTRING_INDEX(hof_champion.title, 'Division ', -1)
                                    ELSE '' END) = (CASE WHEN hof.title LIKE '%Division%'
                                     THEN SUBSTRING_INDEX(hof.title, 'Division ', -1)
                                                    ELSE '' END) AND
                                   (CASE WHEN hof_champion.title LIKE '%mi-saison%'
                                     THEN 1
                                    ELSE 2 END) = (CASE WHEN hof.title LIKE '%mi-saison%'
                                     THEN 1
                                                   ELSE 2 END) AND
                                   (hof_champion.title NOT LIKE '%Vice%' AND
                                    hof_champion.title NOT LIKE '%Finaliste%')
  JOIN hall_of_fame hof_vice_champion ON
                                        hof_vice_champion.league = hof.league AND
                                        hof_vice_champion.period = hof.period AND
                                        (CASE WHEN hof_vice_champion.title LIKE '%Division%'
                                          THEN SUBSTRING_INDEX(hof_vice_champion.title, 'Division ', -1)
                                         ELSE '' END) = (CASE WHEN hof.title LIKE '%Division%'
                                          THEN SUBSTRING_INDEX(hof.title, 'Division ', -1)
                                                         ELSE '' END) AND
                                        (CASE WHEN hof_vice_champion.title LIKE '%mi-saison%'
                                          THEN 1
                                         ELSE 2 END) = (CASE WHEN hof.title LIKE '%mi-saison%'
                                          THEN 1
                                                        ELSE 2 END) AND
                                        (hof_vice_champion.title LIKE '%Vice%' OR
                                         hof_vice_champion.title LIKE '%Finaliste%')
GROUP BY
  hof.league,
  hof.period,
  CASE WHEN hof.title LIKE '%Division%'
    THEN SUBSTRING_INDEX(hof.title, 'Division ', -1)
  ELSE '' END,
  CASE WHEN hof.title LIKE '%mi-saison%'
    THEN 1
  ELSE 2 END
ORDER BY hof.league,
  CASE WHEN hof.title LIKE '%mi-saison%'
    THEN 1
  ELSE 2 END,
  CASE WHEN hof.title LIKE '%Division%'
    THEN SUBSTRING_INDEX(hof.title, 'Division ', -1)
  ELSE '' END";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getGymnasiums()
{
    global $db;
    conn_db();
    $sql = "SELECT 
        id, 
        nom, 
        adresse, 
        code_postal, 
        ville, 
        gps, 
        CONCAT(ville, ' - ', nom, ' - ', adresse) AS full_name,
        nb_terrain
        FROM gymnase
        ORDER BY ville, nom, adresse";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getClubs()
{
    global $db;
    conn_db();
    $sql = "SELECT 
        id, 
        nom
        FROM clubs
        ORDER BY nom";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getCompetitions()
{
    global $db;
    conn_db();
    $sql = "SELECT 
        c.id,
        c.code_competition,
        c.libelle,
        c.id_compet_maitre,
        IFNULL(DATE_FORMAT(c.start_date, '%d/%m/%Y'), '') AS start_date,
        d.date_limite AS limit_date
        FROM competitions c
        LEFT JOIN dates_limite d ON d.code_competition = c.code_competition
        ORDER BY libelle";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getCompetition($code_competition)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        id,
        code_competition,
        libelle,
        id_compet_maitre,
        IFNULL(DATE_FORMAT(start_date, '%d/%m/%Y'), '') AS start_date
        FROM competitions
        WHERE code_competition = '$code_competition'
        ORDER BY libelle";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return $results[0];
}

function saveUser()
{
    global $db;
    $inputs = array(
        'id' => filter_input(INPUT_POST, 'id'),
        'login' => filter_input(INPUT_POST, 'login'),
        'email' => filter_input(INPUT_POST, 'email'),
        'password' => filter_input(INPUT_POST, 'password'),
        'id_team' => filter_input(INPUT_POST, 'id_team')
    );
    if (empty($inputs['id'])) {
        if (isUserExists($inputs['login'])) {
            return false;
        }
    }
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " comptes_acces SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
                continue;
            case 'id_team':
                if (strlen($value) === 0) {
                    $sql .= "id_equipe = NULL,";
                } else {
                    $sql .= "id_equipe = $value,";
                }
                break;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    if (empty($inputs['id'])) {
        $login = $inputs['login'];
        $comment = "Creation d'un nouvel utilisateur : $login";
        addActivity($comment);
    } else {
        $dirtyFields = filter_input(INPUT_POST, 'dirtyFields');
        if ($dirtyFields) {
            $fieldsArray = explode(',', $dirtyFields);
            foreach ($fieldsArray as $fieldName) {
                $fieldValue = filter_input(INPUT_POST, $fieldName);
                $login = $inputs['login'];
                $comment = "$login : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
                if ($fieldName === 'password') {
                    $comment = "$login : Modification du champ $fieldName";
                }
                addActivity($comment);
            }
        }
    }
    return true;
}

function saveGymnasium()
{
    global $db;
    $inputs = array(
        'id' => filter_input(INPUT_POST, 'id'),
        'nom' => filter_input(INPUT_POST, 'nom'),
        'adresse' => filter_input(INPUT_POST, 'adresse'),
        'code_postal' => filter_input(INPUT_POST, 'code_postal'),
        'ville' => filter_input(INPUT_POST, 'ville'),
        'gps' => filter_input(INPUT_POST, 'gps'),
        'nb_terrain' => filter_input(INPUT_POST, 'nb_terrain')
    );
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " gymnase SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
                continue;
            case 'code_postal':
                if (strlen($value) === 0) {
                    $sql .= "$key = NULL,";
                } else {
                    $sql .= "$key = $value,";
                }
                break;
            case 'nb_terrain':
                $sql .= "$key = $value,";
                break;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

/**
 * @throws Exception
 */
function saveCompetition()
{
    global $db;
    $inputs = filter_input_array(INPUT_POST);
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " competitions SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
            case 'dirtyFields':
                continue;
            case 'start_date':
                $sql .= "$key = DATE(STR_TO_DATE('$value', '%d/%m/%Y')),";
                break;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    return;
}

/**
 * @throws Exception
 */
function saveBlacklistGymnase()
{
    global $db;
    $inputs = filter_input_array(INPUT_POST);
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " blacklist_gymnase SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
            case 'dirtyFields':
                continue;
            case 'closed_date':
                $sql .= "$key = DATE(STR_TO_DATE('$value', '%d/%m/%Y')),";
                break;
            case 'id_gymnase':
                $sql .= "$key = $value,";
                break;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    return;
}

function saveClub()
{
    global $db;
    $inputs = array(
        'id' => filter_input(INPUT_POST, 'id'),
        'nom' => filter_input(INPUT_POST, 'nom')
    );
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " clubs SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
                continue;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    disconn_db();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

/**
 * @throws Exception
 */
function saveTeam()
{
    global $db;
    $inputs = filter_input_array(INPUT_POST);
    if (isTeamLeader()) {
        $inputs['id_equipe'] = $_SESSION['id_equipe'];
    }
    conn_db();
    if (empty($inputs['id_equipe'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " equipes SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id_equipe':
            case 'dirtyFields':
                continue;
            case 'id_club':
                $sql .= "$key = $value,";
                break;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id_equipe'])) {

    } else {
        $sql .= " WHERE id_equipe=" . $inputs['id_equipe'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    if (!empty($inputs['id_equipe'])) {
        saveTeamPhoto($inputs['id_equipe']);
    }
    return;
}

/**
 * @throws Exception
 */
function saveRank()
{
    global $db;
    $inputs = filter_input_array(INPUT_POST);
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " classements SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
            case 'dirtyFields':
                continue;
            case 'id_equipe':
            case 'rank_start':
                $sql .= "$key = $value,";
                break;
            case 'division':
                if (is_numeric($value)) {
                    $sql .= "$key = $value,";
                } else {
                    $sql .= "$key = '$value',";
                }
                break;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    return;
}

/**
 * @throws Exception
 */
function saveDay()
{
    global $db;
    $inputs = filter_input_array(INPUT_POST);
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " journees SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
            case 'dirtyFields':
                continue;
            case 'numero':
                $sql .= "numero = $value,";
                break;
            case 'start_date':
                $sql .= "$key = DATE(STR_TO_DATE('$value', '%d/%m/%Y')),";
                break;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id=" . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    return;
}

/**
 * @throws Exception
 */
function saveHallOfFame()
{
    global $db;
    $inputs = filter_input_array(INPUT_POST);
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " hall_of_fame SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
            case 'dirtyFields':
                continue;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id'])) {

    } else {
        $sql .= " WHERE id = " . $inputs['id'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    return;
}

/**
 * @throws Exception
 */
function saveLimitDate()
{
    global $db;
    $inputs = filter_input_array(INPUT_POST);
    conn_db();
    if (empty($inputs['id_date'])) {
        $sql = "INSERT INTO";
    } else {
        $sql = "UPDATE";
    }
    $sql .= " dates_limite SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id_date':
            case 'dirtyFields':
                continue;
            default:
                $sql .= "$key = '$value',";
                break;
        }
    }
    $sql = trim($sql, ',');
    if (empty($inputs['id_date'])) {

    } else {
        $sql .= " WHERE id_date = " . $inputs['id_date'];
    }
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        $message = mysqli_error($db);
        disconn_db();
        throw new Exception($message);
    }
    disconn_db();
    return;
}

function getAlerts()
{
    $results = array();
    if (isAdmin()) {
        return json_encode($results);
    }
    if (!isTeamLeader()) {
        return json_encode($results);
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sessionLogin = $_SESSION['login'];
    if (!hasEnoughPlayers($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Pas assez de joueurs dans l'équipe",
            'criticity' => 'error',
            'expected_action' => 'showHelpAddPlayer'
        );
    }
    if (!hasEnoughWomen($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Pas assez de filles dans l'équipe",
            'criticity' => 'error',
            'expected_action' => 'showHelpAddPlayer'
        );
    }
    if (!hasEnoughMen($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Pas assez de garçons dans l'équipe",
            'criticity' => 'error',
            'expected_action' => 'showHelpAddPlayer'
        );
    }
    if (!hasLeader($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Responsable d'équipe non défini",
            'criticity' => 'error',
            'expected_action' => 'showHelpSelectLeader'
        );
    }
    if (!hasViceLeader($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Responsable suppléant d'équipe non défini",
            'criticity' => 'warning',
            'expected_action' => 'showHelpSelectViceLeader'
        );
    }
    if (!hasCaptain($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Capitaine d'équipe non défini",
            'criticity' => 'error',
            'expected_action' => 'showHelpSelectCaptain'
        );
    }
    if (!hasTimeSlot($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Pas de gymnase de réception",
            'criticity' => 'info',
            'expected_action' => 'showHelpSelectTimeSlot'
        );
    }
    if (!hasAnyPhone($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Pas de numéro de téléphone",
            'criticity' => 'error',
            'expected_action' => 'showHelpAddPhoneNumber'
        );
    }
    if (!hasAnyEmail($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Pas d'email",
            'criticity' => 'error',
            'expected_action' => 'showHelpAddEmail'
        );
    }
    if (hasInactivePlayers($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Joueurs inactifs",
            'criticity' => 'info',
            'expected_action' => 'showHelpInactivePlayers'
        );
    }
    if (hasNotLicencedPlayers($sessionIdEquipe)) {
        $results[] = array(
            'owner' => $sessionLogin,
            'issue' => "Joueurs sans licence",
            'criticity' => 'error',
            'expected_action' => 'showHelpPlayersWithoutLicenceNumber'
        );
    }
    return json_encode($results);
}

function hasNotLicencedPlayers($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        COUNT(*) AS cnt 
        FROM joueur_equipe je 
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND (j.num_licence = '' OR j.num_licence IS NULL)";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function hasEnoughPlayers($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        COUNT(*) AS cnt, 
        e.code_competition
        FROM joueur_equipe je 
        JOIN equipes e ON e.id_equipe = je.id_equipe
        WHERE je.id_equipe = $sessionIdEquipe";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    $minCount = 0;
    switch ($results[0]['code_competition']) {
        case 'm':
        case 'c':
        case 'cf':
            $minCount = 6;
            break;
        case 'mo':
        case 'f':
        case 't':
        case 'ff':
        case 'kh':
        case 'kf':
            $minCount = 4;
            break;
        default:
            break;
    }
    if (intval($results[0]['cnt']) < $minCount) {
        return false;
    }
    return true;
}

function hasInactivePlayers($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        COUNT(*) AS cnt 
        FROM joueur_equipe je 
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND j.est_actif+0 = 0";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function hasEnoughWomen($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        COUNT(*) AS cnt, 
        e.code_competition
        FROM joueur_equipe je 
        JOIN equipes e ON e.id_equipe = je.id_equipe
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND j.sexe = 'F'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    $minCount = 0;
    switch ($results[0]['code_competition']) {
        case 'm':
        case 'c':
        case 'cf':
            $minCount = 0;
            break;
        case 'f':
        case 't':
        case 'ff':
            $minCount = 4;
            break;
        case 'kh':
        case 'kf':
            $minCount = 2;
            break;
        case 'mo':
            $minCount = 1;
            break;
        default:
            break;
    }
    if (intval($results[0]['cnt']) < $minCount) {
        return false;
    }
    return true;
}

function hasEnoughMen($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT 
        COUNT(*) AS cnt, 
        e.code_competition
        FROM joueur_equipe je 
        JOIN equipes e ON e.id_equipe = je.id_equipe
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND j.sexe = 'M'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    $minCount = 0;
    switch ($results[0]['code_competition']) {
        case 'm':
        case 'c':
        case 'cf':
        case 'f':
        case 't':
        case 'ff':
        case 'kh':
        case 'kf':
            $minCount = 0;
            break;
        case 'mo':
            $minCount = 1;
            break;
        default:
            break;
    }
    if (intval($results[0]['cnt']) < $minCount) {
        return false;
    }
    return true;
}

function hasLeader($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_equipe = $sessionIdEquipe AND is_leader+0 > 0";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function hasViceLeader($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_equipe = $sessionIdEquipe AND is_vice_leader+0 > 0";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function hasCaptain($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_equipe = $sessionIdEquipe AND is_captain+0 > 0";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function hasTimeSlot($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM creneau WHERE id_equipe = $sessionIdEquipe";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function hasAnyPhone($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe je
        JOIN joueurs j ON j.id=je.id_joueur AND (
            (j.telephone IS NOT NULL AND j.telephone != '')
            OR 
            (j.telephone2 IS NOT NULL AND j.telephone2 != '')
            )
        WHERE je.id_equipe = $sessionIdEquipe 
        AND (je.is_leader+0 > 0 OR je.is_vice_leader+0 > 0)";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function hasAnyEmail($sessionIdEquipe)
{
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe je
        JOIN joueurs j ON j.id=je.id_joueur AND (
            (j.email IS NOT NULL AND j.email != '')
            OR 
            (j.email2 IS NOT NULL AND j.email2 != '')
        )
        WHERE je.id_equipe = $sessionIdEquipe 
        AND (je.is_leader+0 > 0 OR je.is_vice_leader+0 > 0)";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

/**
 * for each competition id
 * - get competition date
 * - for each division
 * -- get leader
 * -- insert into hall of fame the leader
 * -- get vice-leader
 * -- insert into hall of fame the vice-leader
 * @throws Exception
 */
function generateHallOfFame()
{
    $inputs = filter_input_array(INPUT_POST);
    if (empty($inputs['ids'])) {
        throw new Exception("Aucune compétition sélectionnée !");
    }
    $ids = explode(',', $inputs['ids']);
    foreach ($ids as $id) {
        require_once __DIR__ . '/../classes/CompetitionManager.php';
        $competition_manager = new CompetitionManager();
        $competitions = $competition_manager->getCompetitions("c.id = $id");
        if (count($competitions) !== 1) {
            throw new Exception("Une seule compétition doit être trouvée !");
        }
        if (!$competition_manager->isCompetitionOver($competitions[0]['id'])) {
            throw new Exception("La compétition n'est pas terminée !!!");
        }
        $competition_date = DateTime::createFromFormat("d/m/Y", $competitions[0]['start_date']);
        require_once __DIR__ . '/../classes/RankManager.php';
        $rank_manager = new RankManager();
        $divisions = $rank_manager->getDivisionsFromCompetition($competitions[0]['code_competition']);
        foreach ($divisions as $division) {
            $leader = $rank_manager->getLeader($competitions[0]['code_competition'], $division['division']);
            $vice_leader = $rank_manager->getViceLeader($competitions[0]['code_competition'], $division['division']);
            require_once __DIR__ . '/../classes/HallOfFameManager.php';
            $hall_of_fame_manager = new HallOfFameManager();
            if (intval($competition_date->format('m')) >= 9) {
                $title_season = " mi-saison ";
                $period = $competition_date->format('Y') . "-" . (intval($competition_date->format('Y')) + 1);
            } else {
                $title_season = " Dept. ";
                $period = (intval($competition_date->format('Y')) - 1) . "-" . $competition_date->format('Y');
            }
            $hall_of_fame_manager->insert(
                "Championne" . $title_season . "de Division " . $division['division'],
                $leader['equipe'],
                $period,
                $competitions[0]['libelle']
            );
            $hall_of_fame_manager->insert(
                "Vice-championne" . $title_season . "de Division " . $division['division'],
                $vice_leader['equipe'],
                $period,
                $competitions[0]['libelle']
            );
        }
    }
}

/**
 * @throws Exception
 */
function generateDays()
{
    $inputs = filter_input_array(INPUT_POST);
    if (empty($inputs['ids'])) {
        throw new Exception("Aucune compétition sélectionnée !");
    }
    $ids = explode(',', $inputs['ids']);
    foreach ($ids as $id) {
        require_once __DIR__ . '/../classes/CompetitionManager.php';
        $competition_manager = new CompetitionManager();
        $competitions = $competition_manager->getCompetitions("c.id = $id");
        if (count($competitions) !== 1) {
            throw new Exception("Une seule compétition doit être trouvée !");
        }
        if ($competition_manager->isCompetitionStarted($competitions[0]['id'])) {
            throw new Exception("La compétition a déjà commencé !!!");
        }
        require_once __DIR__ . '/../classes/RankManager.php';
        $rank_manager = new RankManager();
        $competition = $competitions[0];
        $code_competition = $competition['code_competition'];
        if (empty($competition['start_date'])) {
            throw new Exception("Date de début de compétition non renseignée");
        }
        require_once __DIR__ . '/../classes/MatchManager.php';
        $match_manager = new MatchManager();
        $match_manager->deleteMatches("code_competition = '$code_competition'");
        require_once __DIR__ . '/../classes/DayManager.php';
        $day_manager = new DayManager();
        $day_manager->deleteDays("code_competition = '$code_competition'");
        $divisions = $rank_manager->getDivisionsFromCompetition($code_competition);
        $rounds_counts = array();
        foreach ($divisions as $division) {
            $teams = $rank_manager->getTeamsFromDivisionAndCompetition($division['division'], $code_competition);
            $teams_count = count($teams);
            if ($teams_count % 2 == 1) {
                $teams_count++;
            }
            if ($code_competition == 'mo') {
                // seule compétition retour
                $rounds_counts[] = ($teams_count - 1) * 2;
            } else {
                $rounds_counts[] = $teams_count - 1;
            }
        }
        for ($round_number = 1; $round_number <= max($rounds_counts); $round_number++) {
            $day_manager->insertDay(
                $code_competition,
                strval($round_number),
                $competition['start_date']
            );
        }
    }
}

/**
 * @throws Exception
 */
function resetCompetition()
{
    $inputs = filter_input_array(INPUT_POST);
    if (empty($inputs['ids'])) {
        throw new Exception("Aucune compétition sélectionnée !");
    }
    $ids = explode(',', $inputs['ids']);
    foreach ($ids as $id) {
        require_once __DIR__ . '/../classes/CompetitionManager.php';
        $competition_manager = new CompetitionManager();
        $competitions = $competition_manager->getCompetitions("c.id = $id");
        if (count($competitions) !== 1) {
            throw new Exception("Une seule compétition doit être trouvée !");
        }
        if ($competition_manager->isCompetitionStarted($competitions[0]['id'])) {
            throw new Exception("La compétition a déjà commencé !!!");
        }
        require_once __DIR__ . '/../classes/RankManager.php';
        $rank_manager = new RankManager();
        $competition = $competitions[0];
        $code_competition = $competition['code_competition'];
        $rank_manager->resetRankPoints($code_competition);
    }
}

/**
 * @throws Exception
 */
function generateMatches()
{
    $inputs = filter_input_array(INPUT_POST);
    if (empty($inputs['ids'])) {
        throw new Exception("Aucune compétition sélectionnée !");
    }
    $ids = explode(',', $inputs['ids']);
    foreach ($ids as $id) {
        require_once __DIR__ . '/../classes/CompetitionManager.php';
        $competition_manager = new CompetitionManager();
        $competitions = $competition_manager->getCompetitions("c.id = $id");
        if (count($competitions) !== 1) {
            throw new Exception("Une seule compétition doit être trouvée !");
        }
        if ($competition_manager->isCompetitionStarted($competitions[0]['id'])) {
            throw new Exception("La compétition a déjà commencé !!!");
        }
        require_once __DIR__ . '/../classes/MatchManager.php';
        $match_manager = new MatchManager();
        $competition = $competitions[0];
        $match_manager->generateMatches($competition);
    }
}