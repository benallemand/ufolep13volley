l<?php

require_once 'db_inc.php';
session_start();

function accentedToNonAccented($str) {
    $unwanted_array = array('?' => 'S', '?' => 's', '?' => 'Z', '?' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
        'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
        'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y');
    return strtr($str, $unwanted_array);
}

function utf8_encode_mix($input, $encode_keys = false) {
    if (is_array($input)) {
        $result = array();
        foreach ($input as $k => $v) {
            $key = ($encode_keys) ? utf8_encode($k) : $k;
            $result[$key] = utf8_encode_mix($v, $encode_keys);
        }
    } else {
        $result = utf8_encode($input);
    }
    return $result;
}

function randomPassword() {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

function isUserExists($login) {
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

function createUser($login, $email, $idTeam) {
    global $db;
    conn_db();
    if (isUserExists($login)) {
        return false;
    }
    if ($idTeam === NULL) {
        $idTeam = 0;
    }
    $password = randomPassword();
    $sql = "INSERT comptes_acces SET id_equipe = $idTeam, login = '$login', email = '$email', password = '$password'";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    addActivity("Creation du compte $login pour l'equipe " . getTeamName($idTeam));
    sendMailNewUser($email, $login, $password, $idTeam);
    return true;
}

function deleteUsers($ids) {
    $explodedIds = explode(',', $ids);
    $logins = array();
    foreach ($explodedIds as $id) {
        $logins[] = getUserLogin($id);
    }
    global $db;
    conn_db();
    $sql = "DELETE FROM comptes_acces WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    foreach ($logins as $login) {
        addActivity("Suppression du compte : $login");
    }
    return true;
}

function deleteGymnasiums($ids) {
    global $db;
    conn_db();
    $sql = "DELETE FROM gymnase WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deleteClubs($ids) {
    global $db;
    conn_db();
    $sql = "DELETE FROM clubs WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deleteTeams($ids) {
    global $db;
    conn_db();
    $sql = "DELETE FROM equipes WHERE id_equipe IN($ids)";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function deletePlayers($ids) {
    $explodedIds = explode(',', $ids);
    $playersFullNames = array();
    foreach ($explodedIds as $id) {
        $playersFullNames[] = getPlayerFullName($id);
    }
    global $db;
    conn_db();
    $sql = "DELETE FROM joueurs WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    foreach ($playersFullNames as $playerFullName) {
        addActivity("Suppression du joueur : $playerFullName");
    }
    return true;
}

function activatePlayers($ids) {
    $explodedIds = explode(',', $ids);
    $playersFullNames = array();
    foreach ($explodedIds as $id) {
        $playersFullNames[] = getPlayerFullName($id);
    }
    global $db;
    conn_db();
    $sql = "UPDATE joueurs SET est_actif = 1 WHERE id IN($ids)";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    foreach ($playersFullNames as $playerFullName) {
        addActivity("Activation du joueur : $playerFullName");
    }
    return true;
}

function logout() {
    session_destroy();
    die('<META HTTP-equiv="refresh" content=0;URL=' . filter_input(INPUT_SERVER, 'HTTP_REFERER') . '>');
}

function login() {
    global $db;
    conn_db();
    $login = filter_input(INPUT_POST, 'login');
    $password = filter_input(INPUT_POST, 'password');
    if (($login === NULL) || ($password === NULL)) {
        mysqli_close($db);
        echo json_encode(utf8_encode_mix(array(
            'success' => false,
            'message' => 'Veuillez remplir les champs de connexion'
        )));
        return;
    }
    $password = addslashes($password);
    $sql = "SELECT ca.id_equipe, ca.login, ca.password, ca.id AS id_user, p.name AS profile_name FROM comptes_acces ca
        LEFT JOIN users_profiles up ON up.user_id=ca.id
        LEFT JOIN profiles p ON p.id=up.profile_id
        WHERE ca.login = '$login' LIMIT 1";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    if (mysqli_num_rows($req) <= 0) {
        mysqli_close($db);
        echo json_encode(utf8_encode_mix(array(
            'success' => false,
            'message' => 'Login incorrect'
        )));
        return;
    }
    $data = mysqli_fetch_assoc($req);
    if ($data['password'] != $password) {
        mysqli_close($db);
        echo json_encode(utf8_encode_mix(array(
            'success' => false,
            'message' => 'Mot de passe invalide'
        )));
        return;
    }
    $_SESSION['id_equipe'] = $data['id_equipe'];
    $_SESSION['login'] = $data['login'];
    $_SESSION['password'] = $data['password'];
    $_SESSION['id_user'] = $data['id_user'];
    $_SESSION['profile_name'] = $data['profile_name'];
    mysqli_close($db);
    echo json_encode(utf8_encode_mix(array(
        'success' => true,
        'message' => 'Connexion OK'
    )));
    return;
}

function getQuickDetails($idEquipe) {
    global $db;
    conn_db();
    $sql = "SELECT 
        e.code_competition, 
        comp.libelle AS libelle_competition, 
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')') AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')') SEPARATOR '\n') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e
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
    return json_encode(utf8_encode_mix(
                    array(
                        'success' => true,
                        'data' => $results[0]
                    )
    ));
}

function getTournaments() {
    global $db;
    conn_db();
    $sql = "SELECT id, code_competition, libelle "
            . "FROM competitions "
            . "WHERE code_competition IN (SELECT DISTINCT code_competition FROM matches) "
            . "ORDER BY libelle ASC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function getTeams() {
    global $db;
    conn_db();
    $sql = "SELECT 
        e.code_competition, 
        comp.libelle AS libelle_competition, 
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')') AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')') SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e
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
    return json_encode(utf8_encode_mix($results));
}

function getWebSites() {
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
    return json_encode(utf8_encode_mix($results));
}

function getLastResults() {
    global $db;
    conn_db();
    $sql = "SELECT 
    c.libelle AS competition, 
    IF(c.code_competition='f' OR c.code_competition='m', CONCAT('Division ', m.division, ' - ', j.nommage), CONCAT('Poule ', m.division, ' - ', j.nommage)) AS division_journee, 
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
    FROM activity a
    JOIN matches m ON m.code_match = SPLIT_STRING(a.comment, ' ', 3)
    JOIN journees j ON j.numero=m.journee AND j.code_competition=m.code_competition
    JOIN competitions c ON c.code_competition =  m.code_competition
    JOIN equipes e1 ON e1.id_equipe =  m.id_equipe_dom
    JOIN equipes e2 ON e2.id_equipe =  m.id_equipe_ext
    WHERE (
    (m.score_equipe_dom!=0 OR m.score_equipe_ext!=0)
    AND (m.date_reception <= CURDATE())
    AND (m.date_reception >= DATE_ADD(CURDATE(), INTERVAL -10 DAY) )
    AND (a.comment LIKE 'Le match % a ete modifie')
    )
    ORDER BY a.activity_date DESC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        switch ($data['code_competition']) {
            case 'm':
                $data['url'] = 'champ_masc.php?d=' . $data['division'];
                $data['rang_dom'] = getTeamRank($data['code_competition'], $data['division'], $data['id_dom']);
                $data['rang_ext'] = getTeamRank($data['code_competition'], $data['division'], $data['id_ext']);
                break;
            case 'f':
                $data['url'] = 'champ_fem.php?d=' . $data['division'];
                $data['rang_dom'] = getTeamRank($data['code_competition'], $data['division'], $data['id_dom']);
                $data['rang_ext'] = getTeamRank($data['code_competition'], $data['division'], $data['id_ext']);
                break;
            case 'kh':
                $data['url'] = 'coupe_kh.php?d=' . $data['division'];
                break;
            case 'kf':
                $data['url'] = 'coupe_kf.php';
                break;
            case 'cf':
                $data['url'] = 'coupe_cf.php';
                break;
            case 'c':
                $data['url'] = 'coupe.php?d=' . $data['division'];
                break;
            case 'po':
                $data['url'] = 'playoff_masc.php?d=' . $data['division'];
                break;
            case 'px':
                $data['url'] = 'playoff_fem.php?d=' . $data['division'];
                break;
            default :
                break;
        }
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function isAdmin() {
    return (isset($_SESSION['profile_name']) && $_SESSION['profile_name'] == "ADMINISTRATEUR");
}

function isTeamSheetAllowedForUser($idTeam) {
    if (isAdmin()) {
        return true;
    }
    if (!isTeamLeader()) {
        return false;
    }
    return isSameRankingTable($idTeam);
}

function isTeamLeader() {
    return (isset($_SESSION['profile_name']) && $_SESSION['profile_name'] == "RESPONSABLE_EQUIPE");
}

function isSameRankingTable($id_equipe) {
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

function getTeamEmail($id) {
    global $db;
    conn_db();
    $sql = "SELECT j.email 
        FROM joueurs j 
        JOIN joueur_equipe je ON je.id_equipe = $id AND je.id_joueur = j.id AND je.is_leader+0 > 0";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_assoc($req)) {
        return $data['email'];
    }
}

function getLimitDate($compet) {
    global $db;
    conn_db();
    $sql = 'SELECT date_limite FROM dates_limite WHERE code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_assoc($req)) {
        echo $data['date_limite'];
    }
}

function getParentCompetition($compet) {
    global $db;
    conn_db();
    $sql = 'SELECT id_compet_maitre FROM competitions WHERE code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_assoc($req)) {
        return $data['id_compet_maitre'];
    }
}

function getPlayersFromTeam($id_equipe) {
    global $db;
    conn_db();
    $sql = "SELECT
        CONCAT(j.nom, ' ', j.prenom, ' (', j.num_licence, ')') AS full_name,
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
        j.show_photo+0 AS show_photo 
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
            if (file_exists("../" . $results[$index]['path_photo']) === FALSE) {
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
    return json_encode(utf8_encode_mix($results));
}

function getConnectedUser() {
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

function sendMailSubmitResult($id1, $id2, $compet, $date) {
    $matchDate = DateTime::createFromFormat('Y-m-d', $date);
    $headers = 'From: "Laurent Gorlier"<laurent.gorlier@ufolep13volley.org>' . "\n";
    $headers .='Reply-To: laurent.gorlier@ufolep13volley.org' . "\n";
    $headers .='Cc: laurent.gorlier@ufolep13volley.org' . "\n";
    $headers .='Bcc: benallemand@gmail.com' . "\n";
    $headers .='Content-Type: text/html; charset="iso-8859-1"' . "\n";
    $headers .='Content-Transfer-Encoding: 8bit';

    $message = '<html><head><title>Saisie Internet des résultats</title></head><body>';
    $message = $message . 'Aux équipes de ' . getTeamName($id1) . ' et ' . getTeamName($id2) . '<BR>';
    $message = $message . 'Comme vous avez dû le lire sur le règlement, la saisie des informations sur le site internet doit être rigoureuse (pour le suivi de la commission Volley et pour l\'intérêt qu\'y portent les joueurs)<BR><BR>';
    $message = $message . 'Pour résumer, sur le site, 10 jours après la date indiquée pour le match (qui peut être en rouge si le match a été reportée), il doit y avoir un résultat affiché.<BR><BR>';
    $message = $message . 'Pour votre match du <b>' . $matchDate->format('d/m/Y') . '</b> cela n\'est pas le cas. Puisqu\'il s\'agit d\'un premier message d\'alerte, nous vous donnons un délai supplémentaire de 5 jours pour que :<BR>';
    $message = $message . '- soit le résultat soit indiqué<BR>';
    $message = $message . '- soit une autre date de match soit affichée (pour cela il faut me la communiquer en tant que responsable des classements)<BR><BR>';
    $message = $message . 'Je vous rappelle que les deux équipes doivent veiller à ce que cette règle soit suivie ; les deux pourraient donc être pénalisées.<BR><BR>';
    $message = $message . 'Cordialement<BR><BR>Laurent Gorlier<BR>Responsable des classements<BR>';
    $message = $message . '</body></html>';

    $dest = getTeamEmail($id1) . "," . getTeamEmail($id2);

    return mail($dest, "[Ufolep 13 Volley] Saisie Internet des résultats", $message, $headers);
}

function getIdsTeamRequestingNextMatches() {
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

function createCsvString($data) {
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

function sendCsvMail($csvData, $body, $to = 'youraddress@example.com', $subject = 'Test email with attachment', $from = 'webmaster@example.com') {
    $multipartSep = '-----' . md5(time()) . '-----';
    $headers = array(
        "From: $from",
        "Reply-To: $from",
        "Bcc: benallemand@gmail.com",
        "Content-Type: multipart/mixed; boundary=\"$multipartSep\""
    );
    $attachment = chunk_split(base64_encode(createCsvString($csvData)));
    $body = "--$multipartSep\r\n"
            . "Content-Type: text/plain; charset=ISO-8859-1; format=flowed\r\n"
            . "Content-Transfer-Encoding: 7bit\r\n"
            . "\r\n"
            . "$body\r\n"
            . "--$multipartSep\r\n"
            . "Content-Type: text/csv\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: attachment; filename=\"file.csv\"\r\n"
            . "\r\n"
            . "$attachment\r\n"
            . "--$multipartSep--";
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function sendMail($body, $to = 'youraddress@example.com', $subject = 'Test email with attachment', $from = 'webmaster@example.com') {
    $headers = array(
        "From: $from",
        "Reply-To: $from",
        "Bcc: benallemand@gmail.com",
        "Content-Type: text/plain"
    );
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function sendMailNewUser($email, $login, $password, $idTeam) {
    $body = "Bonjour,\r\n"
            . "Voici vos Informations de connexion au site http://www.ufolep13volley.org :\r\n"
            . "Identifiant : $login\r\n"
            . "Mot de passe : $password\r\n"
            . "Equipe de rattachement : " . getTeamName($idTeam) . "\r\n"
            . "\r\n"
            . "\r\n"
            . "\r\n"
            . "L'UFOLEP";
    $to = $email;
    $subject = "[UFOLEP13VOLLEY]Identifiants de connexion";
    $from = "laurent.gorlier@ufolep13volley.org";
    if (sendMail($body, $to, $subject, $from) === FALSE) {
        return false;
    }
}

function sendMailNextMatches() {
    global $db;
    $idsTeamRequestingNextMatches = getIdsTeamRequestingNextMatches();
    foreach ($idsTeamRequestingNextMatches as $idTeam) {
        $id = $idTeam['user_id'];
        conn_db();
        $sql = "SELECT 
        e1.nom_equipe AS equipe_domicile, 
        e2.nom_equipe AS equipe_exterieur, 
        m.code_match as code_match, 
        DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS date, 
        m.heure_reception AS heure, 
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')') SEPARATOR ', ') AS creneaux
        FROM matches m
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom 
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        LEFT JOIN creneau cr ON cr.id_equipe = e1.id_equipe
        LEFT JOIN gymnase g ON g.id = cr.id_gymnase
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e1.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        WHERE 
         (m.id_equipe_dom = $id OR id_equipe_ext = $id)
         AND
        (
        m.date_reception >= CURDATE()
        AND 
        m.date_reception < DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        )
        GROUP BY e1.id_equipe
        ORDER BY date_reception ASC";
        $req = mysqli_query($db, $sql);
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        if (count($results) > 0) {
            $body = "Bonjour,\r\n"
                    . "Voici vos matches de la semaine.\r\n"
                    . "Sportivement,\r\n"
                    . "L'UFOLEP";
            $to = getTeamEmail($id);
            $subject = "Liste des matches de la semaine";
            $from = "laurent.gorlier@ufolep13volley.org";
            if (sendCsvMail($results, $body, $to, $subject, $from) === FALSE) {
                return false;
            }
        }
        return true;
    }
}

function sendMailPlayersWithoutLicenceNumber() {
    global $db;
    conn_db();
    $sql = "SELECT 
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
        ORDER BY equipe ASC";
    $req = mysqli_query($db, $sql);
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (count($results) > 0) {
        $emails = array();
        foreach ($results as $record) {
            $emails[] = $record['responsable'];
        }
        $emails = array_unique($emails);
        foreach ($emails as $email) {
            $body = "Bonjour,\r\n"
                    . "Vous recevez cet email car au moins un de vos joueurs n'a pas encore son numéro de licence renseigné sur le site de l'UFOLEP 13 VOLLEY.\r\n"
                    . "Merci de mettre à jour ce numéro de licence dès que vous le connaissez, afin que l'UFOLEP puisse activer votre joueur sur la fiche équipe.\r\n"
                    . "La liste des joueurs concernés est en pièce jointe.\r\n"
                    . "Sportivement,\r\n"
                    . "L'UFOLEP";
            $to = $email;
            $subject = "[UFOLEP13VOLLEY]Joueurs sans numéro de licence";
            $from = "laurent.gorlier@ufolep13volley.org";
            if (sendCsvMail($results, $body, $to, $subject, $from) === FALSE) {
                return false;
            }
        }
    }
    return true;
}

function computeRank($id_equipe, $compet, $division) {
    global $db;
    conn_db();
    $pts_mar_dom = 0;
    $pts_mar_ext = 0;
    $pts_enc_dom = 0;
    $pts_enc_ext = 0;
    $pts_marques = 0;
    $pts_encaisses = 0;
    $sets_mar_dom = 0;
    $sets_mar_ext = 0;
    $sets_enc_dom = 0;
    $sets_enc_ext = 0;
    $sets_marques = 0;
    $sets_encaisses = 0;
    $coeff_sets = 0;
    $coeff_points = 0;
    $match_gag_dom = 0;
    $match_gag_ext = 0;
    $match_per_dom = 0;
    $match_per_ext = 0;
    $match_gagnes = 0;
    $match_perdus = 0;
    $match_joues = 0;
    $points = 0;
    $forfait = 0;
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND forfait_dom = \'1\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $forfait_dom = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND forfait_ext = \'1\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $forfait_ext = $data[0];
    }
    $sql = 'SELECT penalite FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    if (mysqli_num_rows($req) == 1) {
        $data = mysqli_fetch_assoc($req);
        $penalite = $data['penalite'];
    }
    $sql = 'SELECT COUNT(*) FROM matches M WHERE M.id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND M.score_equipe_dom > M.score_equipe_ext';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $match_gag_dom = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches M WHERE M.id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND M.score_equipe_dom < M.score_equipe_ext';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $match_per_dom = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches M WHERE M.id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND M.score_equipe_dom < M.score_equipe_ext';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $match_gag_ext = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches M WHERE M.id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND M.score_equipe_dom > M.score_equipe_ext';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $match_per_ext = $data[0];
    }
    $sql = 'SELECT SUM(score_equipe_dom), SUM(score_equipe_ext) FROM matches WHERE id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $sets_mar_dom = $data[0];
        $sets_enc_dom = $data[1];
    }
    $sql = 'SELECT SUM(score_equipe_dom), SUM(score_equipe_ext) FROM matches WHERE id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $sets_enc_ext = $data[0];
        $sets_mar_ext = $data[1];
    }
    $sql = 'SELECT SUM(set_1_dom), SUM(set_2_dom), SUM(set_3_dom), SUM(set_4_dom), SUM(set_5_dom), '
            . 'SUM(set_1_ext), SUM(set_2_ext), SUM(set_3_ext), SUM(set_4_ext), SUM(set_5_ext) '
            . 'FROM matches WHERE id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $pts_mar_dom = $data[0] + $data[1] + $data[2] + $data[3] + $data[4];
        $pts_enc_dom = $data[5] + $data[6] + $data[7] + $data[8] + $data[9];
    }
    $sql = 'SELECT SUM(set_1_dom), SUM(set_2_dom), SUM(set_3_dom), SUM(set_4_dom), SUM(set_5_dom), '
            . 'SUM(set_1_ext), SUM(set_2_ext), SUM(set_3_ext), SUM(set_4_ext), SUM(set_5_ext) '
            . 'FROM matches WHERE id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $pts_enc_ext = $data[0] + $data[1] + $data[2] + $data[3] + $data[4];
        $pts_mar_ext = $data[5] + $data[6] + $data[7] + $data[8] + $data[9];
    }
    $match_gagnes = $match_gag_dom + $match_gag_ext;
    $match_perdus = $match_per_dom + $match_per_ext;
    $match_joues = $match_gagnes + $match_perdus;
    $sets_marques = $sets_mar_dom + $sets_mar_ext;
    $sets_encaisses = $sets_enc_dom + $sets_enc_ext;
    $difference = $sets_marques - $sets_encaisses;
    $forfait = $forfait_dom + $forfait_ext;
    $points = 3 * $match_gagnes + $match_perdus - $forfait - $penalite;
    $pts_marques = $pts_mar_dom + $pts_mar_ext;
    $pts_encaisses = $pts_enc_dom + $pts_enc_ext;
    $points = 3 * $match_gagnes + $match_perdus - $forfait - $penalite;
    $pts_marques = $pts_mar_dom + $pts_mar_ext;
    $pts_encaisses = $pts_enc_dom + $pts_enc_ext;
    if ($pts_encaisses != 0) {
        $coeff_points = ($pts_marques / $pts_encaisses);
    } else {
        $coeff_points = $pts_marques;
    }
    if ($sets_encaisses != 0) {
        $coeff_sets = ($sets_marques / $sets_encaisses);
    } else {
        $coeff_sets = $sets_marques;
    }
    $sqlmaj = 'UPDATE classements SET points = \'' . $points . '\', joues = \'' . $match_joues . '\', gagnes = \'' . $match_gagnes . '\', '
            . 'perdus = \'' . $match_perdus . '\', sets_pour = \'' . $sets_marques . '\', sets_contre = \'' . $sets_encaisses . '\', '
            . 'coeff_sets = \'' . $coeff_sets . '\', points_pour = \'' . $pts_marques . '\', points_contre = \'' . $pts_encaisses . '\', '
            . 'coeff_points = \'' . $coeff_points . '\', difference = \'' . $difference . '\' WHERE id_equipe = \'' . $id_equipe . '\' AND division = \'' . $division . '\' AND code_competition = \'' . $compet . '\'';
    mysqli_query($db, $sqlmaj) or die('Erreur SQL !<br>' . $sqlmaj . '<br>' . mysqli_error($db));
}

function getMatchesLostByForfeitCount($idTeam, $codeCompetition) {
    global $db;
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_dom = \'' . $idTeam . '\' AND code_competition = \'' . $codeCompetition . '\' AND forfait_dom = \'1\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $forfait_dom = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_ext = \'' . $idTeam . '\' AND code_competition = \'' . $codeCompetition . '\' AND forfait_ext = \'1\'';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $forfait_ext = $data[0];
    }
    return $forfait_ext + $forfait_dom;
}

function getRank($compet, $div) {
    global $db;
    conn_db();
    $sql = 'SELECT '
            . 'c.id_equipe AS id_equipe,  '
            . 'c.code_competition AS code_competition,  '
            . 'e.nom_equipe AS equipe,  '
            . 'c.points AS points,  '
            . 'c.joues AS joues,  '
            . 'c.gagnes AS gagnes,  '
            . 'c.perdus AS perdus,  '
            . 'c.sets_pour AS sets_pour,  '
            . 'c.sets_contre AS sets_contre,  '
            . 'c.difference AS diff,  '
            . 'c.coeff_sets AS coeff_s,  '
            . 'c.points_pour AS points_pour,  '
            . 'c.points_contre AS points_contre,  '
            . 'c.coeff_points AS coeff_p,  '
            . 'c.penalite AS penalites  '
            . 'FROM classements c '
            . 'JOIN equipes e ON e.id_equipe = c.id_equipe '
            . 'WHERE c.code_competition = \'' . $compet . '\' AND c.division = \'' . $div . '\' ORDER BY points DESC, difference DESC, coeff_points DESC';
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    $rang = 1;
    while ($data = mysqli_fetch_assoc($req)) {
        $data['rang'] = $rang;
        $data['matches_lost_by_forfeit_count'] = getMatchesLostByForfeitCount($data['id_equipe'], $data['code_competition']);
        $results[] = $data;
        $rang++;
    }
    return json_encode(utf8_encode_mix($results));
}

function getTeamRank($competition, $league, $idTeam) {
    $results = json_decode(getRank($competition, $league), true);
    foreach ($results as $data) {
        if ($data['id_equipe'] === $idTeam) {
            return $data['rang'];
        }
    }
    return '';
}

function addPenalty($compet, $id_equipe) {
    global $db;
    conn_db();
    $sql = 'SELECT penalite,division FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    if (mysqli_num_rows($req) == 1) {
        $data = mysqli_fetch_assoc($req);
        $penalite = $data['penalite'];
        $division = $data['division'];
    }
    $penalite++;
    $sqlmaj = 'UPDATE classements set penalite = \'' . $penalite . '\' WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req2 = mysqli_query($db, $sqlmaj);
    if ($req2 === FALSE) {
        return false;
    }
    computeRank($id_equipe, $compet, $division);
    mysqli_close($db);
    addActivity("Une penalite a ete infligee a l'equipe " . getTeamName($id_equipe));
    return true;
}

function removePenalty($compet, $id_equipe) {
    global $db;
    conn_db();
    $sql = 'SELECT penalite,division FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    if (mysqli_num_rows($req) == 1) {
        $data = mysqli_fetch_assoc($req);
        $penalite = $data['penalite'];
        $division = $data['division'];
    }
    $penalite--;
    if ($penalite < 0) {
        $penalite = 0;
    }
    $sqlmaj = 'UPDATE classements set penalite = \'' . $penalite . '\' WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req2 = mysqli_query($db, $sqlmaj);
    if ($req2 === FALSE) {
        return false;
    }
    computeRank($id_equipe, $compet, $division);
    mysqli_close($db);
    addActivity("Une penalite a ete annulee pour l'equipe " . getTeamName($id_equipe));
    return true;
}

function removeTeamFromCompetition($compet, $id_equipe) {
    global $db;
    conn_db();
    $sql = 'DELETE FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = 'DELETE FROM matches WHERE (id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\') OR (id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\')';
    $req2 = mysqli_query($db, $sql);
    if ($req2 === FALSE) {
        return false;
    }
    mysqli_close($db);
    addActivity("L'equipe " . getTeamName($id_equipe) . " a ete supprimee de la competition " . getTournamentName($compet));
    return true;
}

function certifyMatch($code_match) {
    global $db;
    conn_db();
    $sql = 'UPDATE matches SET certif = 1 WHERE code_match = \'' . $code_match . '\'';
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    mysqli_close($db);
    addActivity("Le match $code_match a ete certifie");
    return true;
}

function modifyMatch($code_match) {
    global $db;
    conn_db();
    $score_equipe_dom = filter_input(INPUT_POST, 'score_equipe_dom');
    $score_equipe_ext = filter_input(INPUT_POST, 'score_equipe_ext');
    $set_1_dom = filter_input(INPUT_POST, 'set_1_dom');
    $set_2_dom = filter_input(INPUT_POST, 'set_2_dom');
    $set_3_dom = filter_input(INPUT_POST, 'set_3_dom');
    $set_4_dom = filter_input(INPUT_POST, 'set_4_dom');
    $set_5_dom = filter_input(INPUT_POST, 'set_5_dom');
    $set_1_ext = filter_input(INPUT_POST, 'set_1_ext');
    $set_2_ext = filter_input(INPUT_POST, 'set_2_ext');
    $set_3_ext = filter_input(INPUT_POST, 'set_3_ext');
    $set_4_ext = filter_input(INPUT_POST, 'set_4_ext');
    $set_5_ext = filter_input(INPUT_POST, 'set_5_ext');
    $code_match = filter_input(INPUT_POST, 'code_match');
    $compet = filter_input(INPUT_POST, 'code_competition');
    $division = filter_input(INPUT_POST, 'division');
    $heure_reception = filter_input(INPUT_POST, 'heure_reception');
    $date_reception = filter_input(INPUT_POST, 'date_reception');
    $date_originale = filter_input(INPUT_POST, 'date_originale');
    $id_equipe_dom = filter_input(INPUT_POST, 'id_equipe_dom');
    $id_equipe_ext = filter_input(INPUT_POST, 'id_equipe_ext');
    if ($date_originale !== null) {
        if ($date_originale !== $date_reception) {
            $report = 1;
        } else {
            $report = 0;
        }
    }
    $total_sets_dom = $set_1_dom + $set_2_dom + $set_3_dom;
    $total_sets_ext = $set_1_ext + $set_2_ext + $set_3_ext;
    if ($total_sets_dom == 0 && $total_sets_ext == 75) {
        $forfait_dom = 1;
    } else {
        $forfait_dom = 0;
    }
    if ($total_sets_dom == 75 && $total_sets_ext == 0) {
        $forfait_ext = 1;
    } else {
        $forfait_ext = 0;
    }
    $sql = "UPDATE matches SET "
            . "score_equipe_dom = '$score_equipe_dom', "
            . "score_equipe_ext = '$score_equipe_ext', "
            . "set_1_dom = '$set_1_dom', "
            . "set_1_ext = '$set_1_ext', "
            . "set_2_dom = '$set_2_dom', "
            . "set_2_ext = '$set_2_ext', "
            . "set_3_dom = '$set_3_dom', "
            . "set_3_ext = '$set_3_ext', "
            . "set_4_dom = '$set_4_dom', "
            . "set_4_ext = '$set_4_ext', "
            . "set_5_dom = '$set_5_dom', "
            . "set_5_ext = '$set_5_ext', "
            . "forfait_dom = '$forfait_dom', "
            . "forfait_ext = '$forfait_ext', "
            . "date_reception = DATE(STR_TO_DATE('$date_reception', '%d/%m/%Y')), "
            . "heure_reception = '$heure_reception', "
            . "report = '$report' "
            . "WHERE code_match = '$code_match'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    computeRank($id_equipe_dom, $compet, $division);
    computeRank($id_equipe_ext, $compet, $division);
    mysqli_close($db);
    addActivity("Le match $code_match a ete modifie");
    return true;
}

function addActivity($comment) {
    global $db;
    conn_db();
    $sessionIdUser = $_SESSION['id_user'];
    $sql = "INSERT activity SET comment=\"$comment\", activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s'), user_id=$sessionIdUser";
    mysqli_query($db, $sql);
    mysqli_close($db);
    return;
}

function modifyMyTeam() {
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    $id_equipe = filter_input(INPUT_POST, 'id_equipe');
    if (!isTeamLeader()) {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    if ($sessionIdEquipe != $id_equipe) {
        return false;
    }
    $id_club = filter_input(INPUT_POST, 'id_club');
    $site_web = filter_input(INPUT_POST, 'web_site');
    $sql = "UPDATE equipes SET 
        web_site='$site_web'
        WHERE id_equipe=$id_equipe";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "UPDATE equipes SET "
            . "id_club=$id_club "
            . "WHERE id_equipe=$id_equipe";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    $champsModifies = filter_input(INPUT_POST, 'dirtyFields');
    if ($champsModifies) {
        $fieldsArray = explode(',', $champsModifies);
        foreach ($fieldsArray as $fieldName) {
            $fieldValue = filter_input(INPUT_POST, $fieldName);
            $comment = "Modification du champ $fieldName, nouvelle valeur : $fieldValue";
            addActivity($comment);
        }
    }
    return true;
}

function modifyMyPassword() {
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $password = filter_input(INPUT_POST, 'password');
    $sql = "UPDATE comptes_acces SET "
            . "password='$password' "
            . "WHERE id_equipe=$sessionIdEquipe";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    mysqli_close($db);
    addActivity("Mot de passe modifie");
    return true;
}

function removeMatch($code_match) {
    global $db;
    conn_db();
    $sql = 'DELETE FROM matches WHERE code_match = \'' . $code_match . '\'';
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    mysqli_close($db);
    addActivity("Le match $code_match a ete supprime");
    return true;
}

function checkNotifyUpdateReport($data) {
    $computedDate = DateTime::createFromFormat('Y-m-d', $data['date_reception']);
    $currentDate = new DateTime();
    $tenDays = DateInterval::createFromDateString('+10 day');
    $fifteenDays = DateInterval::createFromDateString('+15 day');
    $computedDate->add($fifteenDays);
    if ($currentDate > $computedDate) {
        if (intval($data['retard']) == 2) {
            return true;
        }
        if (!setSubmitResultDelay($data['code_match'], 2)) {
            return false;
        }
        return sendMailSubmitResult($data['id_equipe_dom'], $data['id_equipe_ext'], $data['code_competition'], $data['date_reception']);
    }
    $computedDate->sub($fifteenDays);
    $computedDate->add($tenDays);
    if ($currentDate > $computedDate) {
        if (intval($data['retard']) == 1) {
            return true;
        }
        if (!setSubmitResultDelay($data['code_match'], 1)) {
            return false;
        }
        return sendMailSubmitResult($data['id_equipe_dom'], $data['id_equipe_ext'], $data['code_competition'], $data['date_reception']);
    }
    return setSubmitResultDelay($data['code_match'], 0);
}

function setSubmitResultDelay($code_match, $valeur) {
    global $db;
    conn_db();
    $sql = "UPDATE matches SET retard = $valeur WHERE code_match = '$code_match'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    mysqli_close($db);
    return true;
}

function getSqlSelectMatches($whereClause, $orderClause) {
    return "SELECT 
        m.id_match,
        m.code_match,
        m.code_competition,
        c.libelle AS libelle_competition,
        m.division,
        CONCAT(j.nommage, ' : ', j.libelle) AS journee,
        m.id_equipe_dom,
        e1.nom_equipe AS equipe_dom,
        m.id_equipe_ext,
        e2.nom_equipe AS equipe_ext,
        m.score_equipe_dom+0 AS score_equipe_dom,
        m.score_equipe_ext+0 AS score_equipe_ext,
        m.set_1_dom,
        m.set_1_ext,
        m.set_2_dom,
        m.set_2_ext,
        m.set_3_dom,
        m.set_3_ext,
        m.set_4_dom,
        m.set_4_ext,
        m.set_5_dom,
        m.set_5_ext,
        m.heure_reception,
        m.date_reception,
        m.forfait_dom+0 AS forfait_dom,
        m.forfait_ext+0 AS forfait_ext,
        m.certif+0 AS certif,
        m.report+0 AS report,
        m.retard+0 AS retard
        FROM matches m 
        JOIN competitions c ON c.code_competition = m.code_competition
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        JOIN journees j ON j.numero=m.journee AND j.code_competition=m.code_competition " . $whereClause . " " . $orderClause;
}

function getMatches($compet, $div) {
    global $db;
    conn_db();
    $sql = getSqlSelectMatches("WHERE m.code_competition = '$compet' AND m.division = '$div'", "ORDER BY m.date_reception, m.code_match");
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
        if ((intval($data['score_equipe_dom']) == 0) && (intval($data['score_equipe_ext']) == 0)) {
            checkNotifyUpdateReport($data);
        } else {
            setSubmitResultDelay($data['code_match'], 0);
        }
    }
    return json_encode(utf8_encode_mix($results));
}

function getMyMatches() {
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = getSqlSelectMatches("WHERE m.id_equipe_dom = $sessionIdEquipe OR m.id_equipe_ext = $sessionIdEquipe", "ORDER BY m.date_reception, m.code_match");
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function getMyTeam() {
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
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')') AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')') SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e
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
    return json_encode(utf8_encode_mix($results));
}

function getMyPreferences() {
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT r.registry_value AS is_remind_matches FROM registry r
        WHERE r.registry_key = 'users.$sessionIdEquipe.is_remind_matches'";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function saveMyPreferences() {
    global $db;
    conn_db();
    if (isAdmin()) {
        return false;
    }
    if (!isTeamLeader()) {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $inputs = array(
        'is_remind_matches' => filter_input(INPUT_POST, 'is_remind_matches')
    );
    if (isRegistryKeyPresent("users.$sessionIdEquipe.is_remind_matches")) {
        $sql = "UPDATE registry SET registry_value = '" . $inputs['is_remind_matches'] . "' WHERE registry_key = 'users.$sessionIdEquipe.is_remind_matches'";
    } else {
        $sql = "INSERT INTO registry SET registry_value = '" . $inputs['is_remind_matches'] . "', registry_key = 'users.$sessionIdEquipe.is_remind_matches'";
    }
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function isRegistryKeyPresent($key) {
    global $db;
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM registry WHERE registry_key = '$key'";
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

function getPlayersPdf($idTeam, $rootPath = '../', $doHideInactivePlayers = false) {
    if ($idTeam === NULL) {
        return false;
    }
    global $db;
    conn_db();
    if (!isTeamSheetAllowedForUser($idTeam)) {
        return false;
    }
    $sql = "SELECT 
        CONCAT(j.nom, ' ', j.prenom, ' (', j.num_licence, ')') AS full_name, 
        CONCAT(UPPER(LEFT(j.prenom, 1)), LOWER(SUBSTRING(j.prenom, 2))) AS prenom, 
        UPPER(j.nom) AS nom, 
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
        j.show_photo+0 AS show_photo 
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
            if (file_exists($rootPath . $results[$index]['path_photo']) === FALSE) {
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
    return json_encode(utf8_encode_mix($results));
}

function getPlayers() {
    global $db;
    conn_db();
    $sql = "SELECT
    CONCAT(j.nom, ' ', j.prenom, ' (', j.num_licence, ')') AS full_name,
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
    GROUP_CONCAT( CONCAT(e.nom_equipe, '(',e.code_competition,')') SEPARATOR ', ') AS teams_list
FROM joueurs j 
LEFT JOIN joueur_equipe je ON je.id_joueur = j.id
LEFT JOIN equipes e ON e.id_equipe=je.id_equipe
LEFT JOIN clubs c ON c.id = j.id_club
LEFT JOIN photos p ON p.id = j.id_photo
GROUP BY full_name";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function getProfiles() {
    global $db;
    conn_db();
    $sql = "SELECT id, name FROM profiles";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function getTeamsListForCaptain($playerId) {
    global $db;
    $teams = array();
    conn_db();
    $sql = "SELECT CONCAT(e.nom_equipe, '(',e.code_competition,')') AS team FROM joueur_equipe je JOIN equipes e ON e.id_equipe=je.id_equipe
    WHERE je.id_joueur = $playerId AND is_captain+0=1";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    while ($data = mysqli_fetch_array($req)) {
        $teams[] = $data['team'];
    }
    return implode(',', $teams);
}

function isPlayerInTeam($idPlayer, $idTeam) {
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

function updateMyTeamCaptain($idPlayer) {
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
    mysqli_close($db);
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau capitaine : " . getPlayerFullName($idPlayer));
    return true;
}

function updateMyTeamViceLeader($idPlayer) {
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
    mysqli_close($db);
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau suppleant : " . getPlayerFullName($idPlayer));
    return true;
}

function updateMyTeamLeader($idPlayer) {
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
    mysqli_close($db);
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau responsable : " . getPlayerFullName($idPlayer));
    return true;
}

function addPlayerToMyTeam($idPlayer) {
    global $db;
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

function getMyTeamIdClub() {
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

function getPlayersIdClub($idPlayer) {
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
    mysqli_close($db);
    return $results[0]['id_club'];
}

function addPlayerToTeam($idPlayer, $idTeam) {
    global $db;
    conn_db();
    if (isPlayerInTeam($idPlayer, $idTeam)) {
        return true;
    }
    $sql = "INSERT joueur_equipe SET id_joueur = $idPlayer, id_equipe = $idTeam";
    $req = mysqli_query($db, $sql);
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    addActivity("Ajout de " . getPlayerFullName($idPlayer) . " a l'equipe " . getTeamName($idTeam));
    return true;
}

function getPlayerFullName($idPlayer) {
    global $db;
    conn_db();
    $sql = "SELECT 
        CONCAT(j.nom, ' ', j.prenom, ' (', j.num_licence, ')') AS player_full_name
        FROM joueurs j
        WHERE j.id = $idPlayer";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysqli_close($db);
    return $results[0]['player_full_name'];
}

function getUserLogin($idUser) {
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
    mysqli_close($db);
    return $results[0]['login'];
}

function getTeamName($idTeam) {
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
    mysqli_close($db);
    return $results[0]['team_name'];
}

function getTournamentName($tournamentCode) {
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
    mysqli_close($db);
    return $results[0]['tournament_name'];
}

function getClubName($idClub) {
    global $db;
    conn_db();
    $sql = "SELECT 
        c.nom as club_name 
        FROM clubs c 
        WHERE c.id = $idClub";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysqli_close($db);
    return $results[0]['club_name'];
}

function getProfileName($idProfile) {
    global $db;
    conn_db();
    $sql = "SELECT 
        p.name as profile_name 
        FROM profiles p 
        WHERE p.id = $idProfile";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysqli_close($db);
    return $results[0]['profile_name'];
}

function addPlayersToClub($idPlayers, $idClub) {
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
    mysqli_close($db);
    foreach (explode(',', $idPlayers) as $idPlayer) {
        addActivity(getPlayerFullName($idPlayer) . " a ete ajoute au club " . getClubName($idClub));
    }
    return true;
}

function hasProfile($idUser) {
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

function addProfileToUsers($idProfile, $idUsers) {
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
            $sql.="WHERE user_id = $idUser";
        }
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            return false;
        }
        mysqli_close($db);
        addActivity(getUserLogin($idUser) . " a obtenu le profil " . getProfileName($idProfile));
    }
    return true;
}

function getIdClubFromIdTeam($idTeam) {
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
    mysqli_close($db);
    return $results[0]['id_club'];
}

function addPlayersToTeam($idPlayers, $idTeam) {
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

function removePlayerFromMyTeam($idPlayer) {
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
    mysqli_close($db);
    addActivity(getPlayerFullName($idPlayer) . " a ete supprime de l'equipe " . getTeamName($idTeam));
    return true;
}

function removeTimeSlot($id) {
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
    mysqli_close($db);
    addActivity("Un créneau a été supprimé");
    return true;
}

function getTeamSheet($idTeam) {
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
        JOIN classements cla ON cla.code_competition=e.code_competition AND cla.id_equipe=e.id_equipe
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
    return json_encode(utf8_encode_mix($results));
}

function insertPhoto($uploadfile, &$idPhoto) {
    global $db;
    conn_db();
    $sql = "INSERT INTO photos SET path_photo = '$uploadfile'";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    $idPhoto = mysqli_insert_id($db);
    mysqli_close($db);
    return true;
}

function linkPlayerToPhoto($idPlayer, $idPhoto) {
    global $db;
    conn_db();
    $sql = "UPDATE joueurs j SET j.id_photo = $idPhoto WHERE id = $idPlayer";
    $req = mysqli_query($db, $sql);
    if ($req === FALSE) {
        return false;
    }
    mysqli_close($db);
    return true;
}

function savePhoto($inputs, $newId = 0) {
    $lastName = $inputs['nom'];
    $firstName = $inputs['prenom'];
    if (empty($_FILES['photo']['name'])) {
        return true;
    }
    $uploaddir = '../players_pics/';
    $iteration = 1;
    $uploadfile = accentedToNonAccented($uploaddir . str_replace(array('-', ' '), '', mb_strtolower($lastName)) . str_replace(array('-', ' '), '', mb_strtolower($firstName)) . $iteration . '.jpg');
    while (file_exists($uploadfile)) {
        $iteration++;
        $uploadfile = accentedToNonAccented($uploaddir . str_replace(array('-', ' '), '', mb_strtolower($lastName)) . str_replace(array('-', ' '), '', mb_strtolower($firstName)) . $iteration . '.jpg');
    }
    $idPhoto = 0;
    if (!insertPhoto(substr($uploadfile, 3), $idPhoto)) {
        return false;
    }
    $idPlayer = $inputs['id'];
    if (empty($inputs['id'])) {
        $idPlayer = $newId;
    }
    if (!linkPlayerToPhoto($idPlayer, $idPhoto)) {
        return false;
    }
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadfile)) {
        addActivity("Une nouvelle photo a ete transmise pour le joueur $firstName $lastName");
        return true;
    }
    return false;
}

function isPlayerExists($licenceNumber) {
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

function isProfileExists($name) {
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

function savePlayer() {
    global $db;
    $inputs = array(
        'prenom' => filter_input(INPUT_POST, 'prenom'),
        'nom' => filter_input(INPUT_POST, 'nom'),
        'telephone' => filter_input(INPUT_POST, 'telephone'),
        'email' => filter_input(INPUT_POST, 'email'),
        'num_licence' => filter_input(INPUT_POST, 'num_licence'),
        'sexe' => filter_input(INPUT_POST, 'sexe'),
        'departement_affiliation' => filter_input(INPUT_POST, 'departement_affiliation'),
        'est_actif' => filter_input(INPUT_POST, 'est_actif'),
        'id_club' => filter_input(INPUT_POST, 'id_club'),
        'id_team' => filter_input(INPUT_POST, 'id_team'),
        'telephone2' => filter_input(INPUT_POST, 'telephone2'),
        'email2' => filter_input(INPUT_POST, 'email2'),
        'est_responsable_club' => filter_input(INPUT_POST, 'est_responsable_club'),
        'id' => filter_input(INPUT_POST, 'id'),
        'show_photo' => filter_input(INPUT_POST, 'show_photo')
    );
    if (empty($inputs['id'])) {
        if (isPlayerExists($inputs['num_licence'])) {
            return false;
        }
    }
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO ";
    } else {
        $sql = "UPDATE ";
    }
    $sql .= "joueurs SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
            case 'id_team':
                continue;
            case 'departement_affiliation':
            case 'id_club':
                $sql .= "$key = $value,";
                break;
            case 'est_actif':
            case 'est_responsable_club':
            case 'show_photo':
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
    $newId = mysqli_insert_id($db);
    mysqli_close($db);
    if (empty($inputs['id'])) {
        if (!empty($inputs['id_team'])) {
            if ($newId > 0) {
                if (!addPlayerToMyTeam($newId)) {
                    return false;
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
    return savePhoto($inputs, $newId);
}

function saveTimeSlot() {
    global $db;
    $inputs = array(
        'id' => filter_input(INPUT_POST, 'id'),
        'id_equipe' => filter_input(INPUT_POST, 'id_equipe'),
        'id_gymnase' => filter_input(INPUT_POST, 'id_gymnase'),
        'jour' => filter_input(INPUT_POST, 'jour'),
        'heure' => filter_input(INPUT_POST, 'heure')
    );
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO ";
    } else {
        $sql = "UPDATE ";
    }
    $sql .= "creneau SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id':
                continue;
            case 'id_equipe':
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
        return false;
    }
    $newId = mysqli_insert_id($db);
    mysqli_close($db);
    if (empty($inputs['id'])) {
        $teamName = getTeamName($inputs['id_equipe']);
        $comment = "Creation d'un nouveau creneau pour l'équipe $teamName";
    } else {
        $teamName = getTeamName($inputs['id_equipe']);
        $comment = "Modification d'un creneau existant pour l'équipe $teamName";
    }
    addActivity($comment);
    return true;
}

function saveProfile() {
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
        $sql = "INSERT INTO ";
    } else {
        $sql = "UPDATE ";
    }
    $sql .= "profiles SET ";
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
    mysqli_close($db);
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

function getUsers() {
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
    return json_encode(utf8_encode_mix($results));
}

function getWeekSchedule() {
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
    return json_encode(utf8_encode_mix($results));
}

function getGymnasiums() {
    global $db;
    conn_db();
    $sql = "SELECT 
        id, 
        nom, 
        adresse, 
        code_postal, 
        ville, 
        gps, 
        CONCAT(ville, ' - ', nom, ' - ', adresse) AS full_name 
        FROM gymnase
        ORDER BY ville, nom, adresse";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function getClubs() {
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
    return json_encode(utf8_encode_mix($results));
}

function getCompetitions() {
    global $db;
    conn_db();
    $sql = "SELECT 
        id,
        code_competition,
        libelle,
        id_compet_maitre
        FROM competitions
        ORDER BY libelle";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function saveUser() {
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
        $sql = "INSERT INTO ";
    } else {
        $sql = "UPDATE ";
    }
    $sql .= "comptes_acces SET ";
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
    mysqli_close($db);
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

function saveGymnasium() {
    global $db;
    $inputs = array(
        'id' => filter_input(INPUT_POST, 'id'),
        'nom' => filter_input(INPUT_POST, 'nom'),
        'adresse' => filter_input(INPUT_POST, 'adresse'),
        'code_postal' => filter_input(INPUT_POST, 'code_postal'),
        'ville' => filter_input(INPUT_POST, 'ville'),
        'gps' => filter_input(INPUT_POST, 'gps')
    );
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO ";
    } else {
        $sql = "UPDATE ";
    }
    $sql .= "gymnase SET ";
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
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function saveClub() {
    global $db;
    $inputs = array(
        'id' => filter_input(INPUT_POST, 'id'),
        'nom' => filter_input(INPUT_POST, 'nom')
    );
    conn_db();
    if (empty($inputs['id'])) {
        $sql = "INSERT INTO ";
    } else {
        $sql = "UPDATE ";
    }
    $sql .= "clubs SET ";
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
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function saveTeam() {
    global $db;
    $inputs = array(
        'id_equipe' => filter_input(INPUT_POST, 'id_equipe'),
        'code_competition' => filter_input(INPUT_POST, 'code_competition'),
        'nom_equipe' => filter_input(INPUT_POST, 'nom_equipe'),
        'id_club' => filter_input(INPUT_POST, 'id_club')
    );
    conn_db();
    if (empty($inputs['id_equipe'])) {
        $sql = "INSERT INTO ";
    } else {
        $sql = "UPDATE ";
    }
    $sql .= "equipes SET ";
    foreach ($inputs as $key => $value) {
        switch ($key) {
            case 'id_equipe':
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
    mysqli_close($db);
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function getTimeSlots() {
    $isTeamLeader = isTeamLeader();
    global $db;
    conn_db();
    $sql = "SELECT 
        c.id, 
        c.id_gymnase, 
        c.id_equipe, 
        c.jour, 
        c.heure, 
        CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse) AS gymnasium_full_name, 
        CONCAT(e.nom_equipe, ' (', cl.nom, ') (', comp.libelle, ')') AS team_full_name
        FROM creneau c
        JOIN gymnase g ON g.id = c.id_gymnase
        JOIN equipes e ON e.id_equipe = c.id_equipe
        JOIN clubs cl ON cl.id = e.id_club
        JOIN competitions comp ON comp.code_competition = e.code_competition";
    if ($isTeamLeader) {
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $sql .= " WHERE c.id_equipe = $sessionIdEquipe";
    }
    $sql .= " ORDER BY team_full_name, gymnasium_full_name";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function getActivity() {
    $isTeamLeader = isTeamLeader();
    global $db;
    conn_db();
    $sql = "SELECT 
        DATE_FORMAT(a.activity_date, '%d/%m/%Y %H:%i:%s') AS date, 
        e.nom_equipe, 
        c.libelle AS competition, 
        a.comment AS description, 
        ca.login AS utilisateur, 
        ca.email AS email_utilisateur 
        FROM activity a
        LEFT JOIN comptes_acces ca ON ca.id=a.user_id
        LEFT JOIN equipes e ON e.id_equipe=ca.id_equipe
        LEFT JOIN competitions c ON c.code_competition=e.code_competition";
    if ($isTeamLeader) {
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $sql .= " WHERE e.id_equipe = $sessionIdEquipe";
    }
    $sql .= " ORDER BY a.activity_date DESC";
    $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
    $results = array();
    while ($data = mysqli_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(utf8_encode_mix($results));
}

function getAlerts() {
    $results = array();
    if (isAdmin()) {
        return json_encode(utf8_encode_mix($results));
    }
    if (!isTeamLeader()) {
        return json_encode(utf8_encode_mix($results));
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
    return json_encode(utf8_encode_mix($results));
}

function hasNotLicencedPlayers($sessionIdEquipe) {
    global $db;
    conn_db();
    $sql = "SELECT 
        COUNT(*) AS cnt 
        FROM joueur_equipe je 
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND j.num_licence = ''";
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

function hasEnoughPlayers($sessionIdEquipe) {
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
        case 'pf':
            $minCount = 6;
            break;
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

function hasInactivePlayers($sessionIdEquipe) {
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

function hasEnoughWomen($sessionIdEquipe) {
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
        case 'pf':
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
        default:
            break;
    }
    if (intval($results[0]['cnt']) < $minCount) {
        return false;
    }
    return true;
}

function hasLeader($sessionIdEquipe) {
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

function hasViceLeader($sessionIdEquipe) {
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

function hasCaptain($sessionIdEquipe) {
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

function hasTimeSlot($sessionIdEquipe) {
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

function hasAnyPhone($sessionIdEquipe) {
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

function hasAnyEmail($sessionIdEquipe) {
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
