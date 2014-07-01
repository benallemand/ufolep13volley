<?php

require_once 'db_inc.php';
session_start();

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
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

function isLoginInDatabase($login) {
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM comptes_acces WHERE login = '$login'";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function createUser($login, $idTeam) {
    conn_db();
    if (isLoginInDatabase($login)) {
        return false;
    }
    if ($idTeam === NULL) {
        $idTeam = 0;
    }
    $password = randomPassword();
    $sql = "INSERT comptes_acces SET id_equipe = $idTeam, login = '$login', password = '$password'";
    $req = mysql_query($sql);
    mysql_close();
    if ($req === FALSE) {
        return false;
    }
    addActivity("Creation du compte $login pour l'equipe " . getTeamName($idTeam));
    sendMailNewUser($login, $password, $idTeam);
    return true;
}

function logout() {
    session_destroy();
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

function login() {
    conn_db();
    if ((empty($_POST['login'])) || (empty($_POST['password']))) {
        mysql_close();
        echo json_encode(array(
            'success' => false,
            'message' => 'Veuillez remplir les champs de connexion'
        ));
        return;
    }
    $login = $_POST['login'];
    $password = addslashes($_POST['password']);
    $sql = 'SELECT * FROM comptes_acces WHERE login = \'' . $login . '\' LIMIT 1';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    if (mysql_num_rows($req) <= 0) {
        mysql_close();
        echo json_encode(array(
            'success' => false,
            'message' => 'Login incorrect'
        ));
        return;
    }
    $data = mysql_fetch_assoc($req);
    if ($data['password'] != $password) {
        mysql_close();
        echo json_encode(array(
            'success' => false,
            'message' => 'Mot de passe invalide'
        ));
        return;
    }
    $id_equipe = $data['id_equipe'];
    session_start();
    $_SESSION['login'] = $login;
    $_SESSION['password'] = $password;
    if ($id_equipe == "999") {
        $_SESSION['id_equipe'] = "admin";
    } else {
        $_SESSION['id_equipe'] = $id_equipe;
    }
    mysql_close();
    echo json_encode(array(
        'success' => true,
        'message' => 'Connexion OK'
    ));
    return;
}

function getQuickDetails($idEquipe) {
    conn_db();
    $sql = "SELECT id_equipe, responsable, telephone_1, telephone_2, email, gymnase, localisation, jour_reception, heure_reception "
            . "FROM details_equipes "
            . "WHERE id_equipe=$idEquipe";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode(
            array(
                'success' => true,
                'data' => $results[0]
            )
    );
}

function getTournaments() {
    conn_db();
    $sql = "SELECT id, code_competition, libelle "
            . "FROM competitions "
            . "WHERE code_competition IN (SELECT DISTINCT code_competition FROM matches) "
            . "ORDER BY libelle ASC";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getTeams() {
    conn_db();
    $sql = "SELECT e.id_equipe, e.code_competition, e.nom_equipe, e.id_club, CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')') AS team_full_name "
            . "FROM equipes e "
            . "LEFT JOIN clubs c ON c.id=e.id_club "
            . "LEFT JOIN competitions comp ON comp.code_competition=e.code_competition "
            . "ORDER BY nom_equipe ASC";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getWebSites() {
    conn_db();
    $sql = "SELECT e.nom_equipe, de.site_web FROM details_equipes de
        JOIN equipes e ON e.id_equipe=de.id_equipe
        WHERE site_web!=''
        ORDER BY nom_equipe ASC";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getLastResults() {
    conn_db();
    /** Format UTF8 pour afficher correctement les accents */
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
    FROM matches m
    LEFT JOIN journees j ON j.numero=m.journee AND j.code_competition=m.code_competition
    LEFT JOIN competitions c ON c.code_competition =  m.code_competition
    LEFT JOIN equipes e1 ON e1.id_equipe =  m.id_equipe_dom
    LEFT JOIN equipes e2 ON e2.id_equipe =  m.id_equipe_ext
    WHERE (
    (m.score_equipe_dom!=0 OR m.score_equipe_ext!=0)
    AND (m.date_reception <= CURDATE())
    )
    ORDER BY date_reception DESC LIMIT 100";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
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
            default :
                break;
        }
        $results[] = $data;
    }
    return json_encode($results);
}

function estAdmin() {
    return (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin");
}

function estMemeClassement($id_equipe) {
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return true;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    if ($sessionIdEquipe === $id_equipe) {
        return true;
    }
    conn_db();
    $sql = "select * from classements 
        where division in 
        (select division from classements where id_equipe=$sessionIdEquipe)
        and code_competition in 
        (select code_competition from classements where id_equipe=$sessionIdEquipe);";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $result) {
        if ($result['id_equipe'] === $id_equipe) {
            return true;
        }
    }
    return false;
}

//************************************************************************************************
//************************************************************************************************
function recup_nom_equipe($compet, $id)
//************************************************************************************************
/*
 * * Fonction    : recup_nom_equipe 
 * * Input       : STRING $id
 * * Output      : aucun 
 * * Description : Récupère le nom d'une équipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 15/04/2010
 */ {
    conn_db();
    $sql = 'SELECT nom_equipe FROM equipes WHERE code_competition = \'' . recup_compet_maitre($compet) . '\' and id_equipe = \'' . $id . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
        return $data['nom_equipe'];
    }
}

//************************************************************************************************
//************************************************************************************************
function recup_mail_equipe($id)
//************************************************************************************************
/*
 * * Fonction    : recup_mail_equipe 
 * * Input       : STRING $id
 * * Output      : aucun 
 * * Description : Récupère le mail d'une équipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 16/11/2010
 */ {
    conn_db();
    $sql = 'SELECT email FROM details_equipes WHERE id_equipe = \'' . $id . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
        return $data['email'];
    }
}

//************************************************************************************************
//************************************************************************************************
function affich_infos($compet)
//************************************************************************************************
/*
 * * Fonction    : affich_infos 
 * * Input       : STRING $compet, $div
 * * Output      : $result
 * * Description : Date limite des matches en fonction de la compet et de la division 
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 03/05/2012
 */ {
    conn_db();
    $sql = 'SELECT date_limite FROM dates_limite WHERE code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
        echo $data['date_limite'];
    }
}

//************************************************************************************************
//************************************************************************************************
function recup_compet_maitre($compet)
//************************************************************************************************
/*
 * * Fonction    : recup_compet_maitre 
 * * Input       : STRING $compet
 * * Output      : aucun 
 * * Description : Récupère la compétition maitre m ou f d'une équipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 11/05/2010
 */ {
    conn_db();
    $sql = 'SELECT id_compet_maitre FROM competitions WHERE code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
        return $data['id_compet_maitre'];
    }
}

function getPlayersFromTeam($id_equipe) {
    conn_db();
    $sql = "SELECT
        CONCAT(j.nom, ' ', j.prenom, ' (', j.num_licence, ')') AS full_name,
        j.prenom, 
        j.nom, 
        j.telephone, 
        j.email, 
        j.num_licence, 
        CONCAT('images/joueurs/', UPPER(REPLACE(j.nom, '-', '')), UPPER(LEFT(j.prenom, 1)), LOWER(SUBSTRING(REPLACE(j.prenom, '-', ''),2)), '.jpg') AS path_photo,
        j.sexe, 
        j.departement_affiliation, 
        j.est_actif+0 AS est_actif, 
        j.id_club, 
        j.telephone2, 
        j.email2, 
        CASE 
            WHEN (DATEDIFF(j.date_homologation, CONCAT(YEAR(j.date_homologation), '-08-31')) > 0) THEN 
                CASE 
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation)+1, '-08-31'),CURDATE()) > 0) THEN 1
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation)+1, '-08-31'), CURDATE()) <= 0) THEN 0
                END
            WHEN (DATEDIFF(j.date_homologation, CONCAT(YEAR(j.date_homologation), '-08-31')) <= 0) THEN 
                CASE 
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation), '-08-31'),CURDATE()) > 0) THEN 1
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation), '-08-31'), CURDATE()) <= 0) THEN 0
                END         
        END AS est_licence_valide, 
        j.est_responsable_club+0 AS est_responsable_club, 
        je.is_captain+0 AS is_captain, 
        je.is_vice_leader+0 AS is_vice_leader, 
        je.is_leader+0 AS is_leader, 
        j.id, 
        j.date_homologation,
        j.show_photo+0 AS show_photo 
        FROM joueur_equipe je
        LEFT JOIN joueurs j ON j.id=je.id_joueur
    WHERE id_equipe = $id_equipe";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $index => $result) {
        if ($result['show_photo'] === '1') {
            if (file_exists("../" . $result['path_photo']) === FALSE) {
                switch ($result['sexe']) {
                    case 'M':
                        $results[$index]['path_photo'] = 'images/joueurs/MaleMissingPhoto.png';
                        break;
                    case 'F':
                        $results[$index]['path_photo'] = 'images/joueurs/FemaleMissingPhoto.png';
                        break;
                    default:
                        break;
                }
            }
        } else {
            switch ($result['sexe']) {
                case 'M':
                    $results[$index]['path_photo'] = 'images/joueurs/MalePhotoNotAllowed.png';
                    break;
                case 'F':
                    $results[$index]['path_photo'] = 'images/joueurs/FemalePhotoNotAllowed.png';
                    break;
                default:
                    break;
            }
        }
    }
    return json_encode($results);
}

function isLatLong($localisation) {
    $latLongStrings = explode(',', $localisation);
    if (count($latLongStrings) !== 2) {
        return false;
    }
    if (floatval($latLongStrings[0]) === 0) {
        return false;
    }
    if (floatval($latLongStrings[1]) === 0) {
        return false;
    }
    return true;
}

//************************************************************************************************
//************************************************************************************************
function affich_details_equipe($id_equipe, $compet)
//************************************************************************************************
/*
 * * Fonction    : affich_details_equipe
 * * Input       : STRING $var_id_equipe,$var_id_table
 * * Output      : aucun 
 * * Description : Affiche les détails d'une équipe 
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 23/04/2010 
 */ {
//Connexion à la base
    conn_db();

// on exécute la requête
    $sql = 'SELECT * FROM `details_equipes`  WHERE `id_equipe` = \'' . $id_equipe . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
//on récupère les données et on les affecte
        $nom_equipe = recup_nom_equipe($compet, $id_equipe);
        if (empty($data['responsable'])) {
            $responsable = "-";
        } else {
            $responsable = $data['responsable'];
        }
        if (empty($data['telephone_1'])) {
            $telephone_1 = "-";
        } else {
            $telephone_1 = $data['telephone_1'];
        }
        if (empty($data['telephone_2'])) {
            $telephone_2 = "-";
        } else {
            $telephone_2 = $data['telephone_2'];
        }
        if (empty($data['email'])) {
            $email = "-";
        } else {
            $email = $data['email'];
        }
        if (empty($data['jour_reception'])) {
            $jour_reception = "-";
        } else {
            $jour_reception = $data['jour_reception'];
        }
        if (empty($data['heure_reception'])) {
            $heure_reception = "-";
        } else {
            $heure_reception = $data['heure_reception'];
        }
        if (empty($data['gymnase'])) {
            $gymnase = "-";
        } else {
            $gymnase = $data['gymnase'];
        }
        if (empty($data['localisation'])) {
            $localisation = "-";
        } else {
            $localisation = $data['localisation'];
        }
        if (empty($data['photo'])) {
            $photo = 'images/equipes/inconnu.png';
        } else {
            $photo = 'images/equipes/' . $data['photo'];
        }
        if (empty($data['site_web'])) {
            $site_web = "-";
        } else {
            $site_web = $data['site_web'];
        }
        if (empty($data['fdm'])) {
            $fdm = "-";
        } else {
            $fdm = $data['fdm'];
        }

//on affiche les données
        echo '  <div class="photo_equipe">';
        echo '<img src="' . $photo . '" width="300" height="200">';
        if ($_SESSION['id_equipe'] === $id_equipe) {
            echo '<br/><a href="mailto:photos@ufolep13volley.org" target="_blank">Envoyer une photo d\'équipe</a>';
        }
        echo '</div>';
        echo'  <div class="infos_equipe">';
        echo'    <h1>' . $nom_equipe . ' - Vos détails</h1>';
        echo'    <table>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Responsable :</td>';
        echo'		<td class="datas_details"><img src="ajax/getImageFromText.php?text=' . base64_encode($responsable) . '"/><td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Téléphone 1 :</td>';
        echo'		<td class="datas_details"><img src="ajax/getImageFromText.php?text=' . base64_encode($telephone_1) . '"/><td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Téléphone 2 :</td>';
        echo'		<td class="datas_details"><img src="ajax/getImageFromText.php?text=' . base64_encode($telephone_2) . '"/><td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Email :</td>';
        echo'		<td class="datas_details"><img src="ajax/getImageFromText.php?text=' . base64_encode($email) . '"/><td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Réception le :</td>';
        echo'		<td class="datas_details">' . $jour_reception . '<td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Horaire :</td>';
        echo'		<td class="datas_details">' . $heure_reception . '<td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Gymnase :</td>';
        echo'		<td class="datas_details">' . $gymnase . '<td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Localisation GPS :</td>';
        echo'		<td class="datas_details">' . $localisation . '<td>';
        echo'	  </tr>';
        $localisation = str_replace(' ', '', $localisation);
        if (isLatLong($localisation)) {
            echo'      <tr class="tr_130">';
            echo'		<td class="titre_details">Plan :</td>';
            echo'		<td class="datas_details"><iframe width="450" height="300" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/?ie=UTF8&t=m&q=' . $localisation . '&z=12&output=embed"></iframe><td>';
            echo'	  </tr>';
        }
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Site Web :</td>';
        echo'		<td class="datas_details">' . $site_web . '<td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Fiche Equipe :</td>';
        if (file_exists("fdm/$fdm")) {
            if (estMemeClassement($id_equipe)) {
                echo'		<td class="datas_details"><a href="fdm/' . $fdm . '">Telecharger</a><td>';
            } else {
                echo'		<td class="datas_details">Téléchargement Non Autorisé<td>';
            }
        } else {
            echo'		<td class="datas_details">Fiche Equipe Non Créée !!! <td>';
        }
        echo'	  </tr>';
        echo'    </table>';
        echo'  </div>';
    }

    echo'<div id="flux"></div>';
}

function getConnectedUser() {
    $nom_equipe = '';
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        $nom_equipe = "Administrateur";
    }
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] != "admin") {
        $jsonTeamDetails = json_decode(getMonEquipe());
        $nom_equipe = $jsonTeamDetails[0]->team_full_name;
    }
    return $nom_equipe;
}

//************************************************************************************************
//************************************************************************************************
function affich_connecte()
//************************************************************************************************
/*
 * * Fonction    : affich_connecte
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : Affiche le nom de l'équipe connecté
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 07/05/2010 
 */ {
// On affiche le div
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        $nom_equipe = "Administrateur";
    }
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] != "admin") {
        $nom_equipe = $_SESSION['login'];
    }

    if (isset($_SESSION['id_equipe'])) {
        echo'<div id="deconn">';
        echo'<ul>';
        echo'<li class="admin">Connecté : <span class="grouge">' . $nom_equipe . '</span>';
        echo' | ';
        echo'<span><a href="ajax/logout.php">Se déconnecter</a></span></li>';
        echo'</ul>';
        echo'</div>';
    }
}

function envoi_mail($id1, $id2, $compet, $date) {
    $matchDate = DateTime::createFromFormat('Y-m-d', $date);
    $headers = 'From: "Laurent Gorlier"<laurent.gorlier@ufolep13volley.org>' . "\n";
    $headers .='Reply-To: laurent.gorlier@ufolep13volley.org' . "\n";
    $headers .='Cc: laurent.gorlier@ufolep13volley.org' . "\n";
    $headers .='Bcc: benallemand@gmail.com' . "\n";
    $headers .='Content-Type: text/html; charset="iso-8859-1"' . "\n";
    $headers .='Content-Transfer-Encoding: 8bit';

    $message = '<html><head><title>Saisie Internet des résultats</title></head><body>';
    $message = $message . 'Aux équipes de ' . recup_nom_equipe($compet, $id1) . ' et ' . recup_nom_equipe($compet, $id2) . '<BR>';
    $message = $message . 'Comme vous avez dû le lire sur le règlement, la saisie des informations sur le site internet doit être rigoureuse (pour le suivi de la commission Volley et pour l\'intérêt qu\'y portent les joueurs)<BR><BR>';
    $message = $message . 'Pour résumer, sur le site, 10 jours après la date indiquée pour le match (qui peut être en rouge si le match a été reportée), il doit y avoir un résultat affiché.<BR><BR>';
    $message = $message . 'Pour votre match du <b>' . $matchDate->format('d/m/Y') . '</b> cela n\'est pas le cas. Puisqu\'il s\'agit d\'un premier message d\'alerte, nous vous donnons un délai supplémentaire de 5 jours pour que :<BR>';
    $message = $message . '- soit le résultat soit indiqué<BR>';
    $message = $message . '- soit une autre date de match soit affichée (pour cela il faut me la communiquer en tant que responsable des classements)<BR><BR>';
    $message = $message . 'Je vous rappelle que les deux équipes doivent veiller à ce que cette règle soit suivie ; les deux pourraient donc être pénalisées.<BR><BR>';
    $message = $message . 'Cordialement<BR><BR>Laurent Gorlier<BR>Responsable des classements<BR>';
    $message = $message . '</body></html>';

    $dest = recup_mail_equipe($id1) . "," . recup_mail_equipe($id2);

    return mail($dest, "[Ufolep 13 Volley] Saisie Internet des résultats", $message, $headers);
}

function getIdsTeamRequestingNextMatches() {
    conn_db();
    $sql = "SELECT REPLACE(REPLACE(registry_key, '.is_remind_matches',''), 'users.','') AS user_id FROM registry WHERE registry_key LIKE 'users.%.is_remind_matches' AND registry_value = 'on'";
    $req = mysql_query($sql);
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return $results;
}

function create_csv_string($data) {
    // Open temp file pointer
    if (!$fp = fopen('php://temp', 'w+')) {
        return FALSE;
    }
    $isHeaderWritten = false;
    // Loop data and write to file pointer
    foreach ($data as $line) {
        if (!$isHeaderWritten) {
            fputcsv($fp, array_keys($line));
            $isHeaderWritten = true;
        }
        fputcsv($fp, $line);
    }
    // Place stream pointer at beginning
    rewind($fp);
    // Return the data
    return stream_get_contents($fp);
}

function send_csv_mail($csvData, $body, $to = 'youraddress@example.com', $subject = 'Test email with attachment', $from = 'webmaster@example.com') {
    // This will provide plenty adequate entropy
    $multipartSep = '-----' . md5(time()) . '-----';
    // Arrays are much more readable
    $headers = array(
        "From: $from",
        "Reply-To: $from",
        "Bcc: benallemand@gmail.com",
        "Content-Type: multipart/mixed; boundary=\"$multipartSep\""
    );
    // Make the attachment
    $attachment = chunk_split(base64_encode(create_csv_string($csvData)));
    // Make the body of the message
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
    // Send the email, return the result
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function send_mail($body, $to = 'youraddress@example.com', $subject = 'Test email with attachment', $from = 'webmaster@example.com') {
    $headers = array(
        "From: $from",
        "Reply-To: $from",
        "Bcc: benallemand@gmail.com",
        "Content-Type: text/plain"
    );
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function sendMailNewUser($login, $password, $idTeam) {
    $body = "Bonjour,\r\n"
            . "Voici vos Informations de connexion au site http://www.ufolep13volley.org :\r\n"
            . "Identifiant : $login\r\n"
            . "Mot de passe : $password\r\n"
            . "Equipe de rattachement : " . getTeamName($idTeam) . "\r\n"
            . "\r\n"
            . "\r\n"
            . "\r\n"
            . "L'UFOLEP";
    $to = $login;
    $subject = "[UFOLEP13VOLLEY]Identifiants de connexion";
    $from = "laurent.gorlier@ufolep13volley.org";
    if (send_mail($body, $to, $subject, $from) === FALSE) {
        return false;
    }
}

function sendMailNextMatches() {
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
        de.responsable AS responsable, 
        de.telephone_1 AS telephone, 
        de.email AS email, 
        de.gymnase AS addresse, 
        CONCAT('https://maps.google.com/?ie=UTF8&t=m&q=',de.localisation,'&z=12') AS lien_maps 
        FROM matches m
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom 
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        JOIN details_equipes de ON de.id_equipe=m.id_equipe_dom
        WHERE 
        (m.id_equipe_dom = $id OR id_equipe_ext = $id)
        AND
        (
        m.date_reception >= CURDATE()
        AND 
        m.date_reception < DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        )
        ORDER BY date_reception ASC";
        $req = mysql_query($sql);
        $results = array();
        while ($data = mysql_fetch_assoc($req)) {
            $results[] = $data;
        }
        if (count($results) > 0) {
            $body = "Bonjour,\r\n"
                    . "Voici vos matches de la semaine.\r\n"
                    . "Sportivement,\r\n"
                    . "L'UFOLEP";
            $to = recup_mail_equipe($id);
            $subject = "Liste des matches de la semaine";
            $from = "laurent.gorlier@ufolep13volley.org";
            if (send_csv_mail($results, $body, $to, $subject, $from) === FALSE) {
                return false;
            }
        }
        return true;
    }
}

//************************************************************************************************
//************************************************************************************************
function calcul_classement($id_equipe, $compet, $division)
//************************************************************************************************
/*
 * * Fonction    : calcul_classement 
 * * Input       : STRING $id_equipe,$compet, $div
 * * Output      : aucun 
 * * Description : calcule les points de l'équipe qui dont le score vient d'être modifié
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 06/05/2010 
 */ {//1
//Connexion à la base
    conn_db();

//Initialisation des variables
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

//MATCHES PERDUS PAR FORFAIT  ==========================================================================================
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND forfait_dom = \'1\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $forfait_dom = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND forfait_ext = \'1\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $forfait_ext = $data[0];
    }

//POINTS DE PENALITES ==================================================================================================
    $sql = 'SELECT penalite FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    if (mysql_num_rows($req) == 1) {
        $data = mysql_fetch_assoc($req);
        $penalite = $data['penalite'];
    }

//MATCHES GAGNES A 5 JOUEURS ===========================================================================================
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND gagnea5_dom = \'1\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $gagnea5_dom = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND gagnea5_ext = \'1\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $gagnea5_ext = $data[0];
    }

//MATCHES GAGNES ET PERDUS =============================================================================================
//MATCHES GAGNES
    $sql = 'SELECT COUNT(*) FROM matches M WHERE M.id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND M.score_equipe_dom > M.score_equipe_ext';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $match_gag_dom = $data[0];
    }
//MATCHES PERDUS
    $sql = 'SELECT COUNT(*) FROM matches M WHERE M.id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND M.score_equipe_dom < M.score_equipe_ext';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $match_per_dom = $data[0];
    }
//PARTIE MATCHES A L'EXTERIEUR
//MATCHES GAGNES
    $sql = 'SELECT COUNT(*) FROM matches M WHERE M.id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND M.score_equipe_dom < M.score_equipe_ext';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $match_gag_ext = $data[0];
    }
//MATCHES PERDUS
    $sql = 'SELECT COUNT(*) FROM matches M WHERE M.id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\' AND M.score_equipe_dom > M.score_equipe_ext';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $match_per_ext = $data[0];
    }
//SETS MARQUES ET ENCAISSES
// A DOMICILE
    $sql = 'SELECT SUM(score_equipe_dom), SUM(score_equipe_ext) FROM matches WHERE id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $sets_mar_dom = $data[0];
        $sets_enc_dom = $data[1];
    }
// A L'EXTERIEUR
    $sql = 'SELECT SUM(score_equipe_dom), SUM(score_equipe_ext) FROM matches WHERE id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $sets_enc_ext = $data[0];
        $sets_mar_ext = $data[1];
    }

//POINTS MARQUES ET ENCAISSES
// A DOMICILE
    $sql = 'SELECT SUM(set_1_dom), SUM(set_2_dom), SUM(set_3_dom), SUM(set_4_dom), SUM(set_5_dom), '
            . 'SUM(set_1_ext), SUM(set_2_ext), SUM(set_3_ext), SUM(set_4_ext), SUM(set_5_ext) '
            . 'FROM matches WHERE id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $pts_mar_dom = $data[0] + $data[1] + $data[2] + $data[3] + $data[4];
        $pts_enc_dom = $data[5] + $data[6] + $data[7] + $data[8] + $data[9];
    }
//A L'EXTERIEUR
    $sql = 'SELECT SUM(set_1_dom), SUM(set_2_dom), SUM(set_3_dom), SUM(set_4_dom), SUM(set_5_dom), '
            . 'SUM(set_1_ext), SUM(set_2_ext), SUM(set_3_ext), SUM(set_4_ext), SUM(set_5_ext) '
            . 'FROM matches WHERE id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $pts_enc_ext = $data[0] + $data[1] + $data[2] + $data[3] + $data[4];
        $pts_mar_ext = $data[5] + $data[6] + $data[7] + $data[8] + $data[9];
    }

// REGROUPEMENT DES RESULTATS
    $match_gagnes = $match_gag_dom + $match_gag_ext;   // Matches gagnés 
    $match_perdus = $match_per_dom + $match_per_ext;  // Matches perdus
    $match_joues = $match_gagnes + $match_perdus;   // Matches joués
    $sets_marques = $sets_mar_dom + $sets_mar_ext;   // Sets marqués
    $sets_encaisses = $sets_enc_dom + $sets_enc_ext;  // Sets encaissés
    $difference = $sets_marques - $sets_encaisses;   // Différence de sets			
    $gagnea5 = $gagnea5_dom + $gagnea5_ext;     // Matches gagnés à 5 joueurs
    $forfait = $forfait_dom + $forfait_ext;     // Matches perdus par forfait

    $points = 3 * $match_gagnes + $match_perdus - $forfait - $gagnea5 - $penalite;
    $pts_marques = $pts_mar_dom + $pts_mar_ext;
    $pts_encaisses = $pts_enc_dom + $pts_enc_ext;

    $points = 3 * $match_gagnes + $match_perdus - $forfait - $gagnea5 - $penalite;
    $pts_marques = $pts_mar_dom + $pts_mar_ext;
    $pts_encaisses = $pts_enc_dom + $pts_enc_ext;



// On évite la division par 0 pour le calcul des points
    if ($pts_encaisses != 0) {
        $coeff_points = ($pts_marques / $pts_encaisses);
    } else {
        $coeff_points = $pts_marques;
    }
// On évite la division par 0 pour le calcul des sets
    if ($sets_encaisses != 0) {
        $coeff_sets = ($sets_marques / $sets_encaisses);
    } else {
        $coeff_sets = $sets_marques;
    }


//MISE A JOUR DE LA BASE
    $sqlmaj = 'UPDATE classements SET points = \'' . $points . '\', joues = \'' . $match_joues . '\', gagnes = \'' . $match_gagnes . '\', '
            . 'perdus = \'' . $match_perdus . '\', sets_pour = \'' . $sets_marques . '\', sets_contre = \'' . $sets_encaisses . '\', '
            . 'coeff_sets = \'' . $coeff_sets . '\', points_pour = \'' . $pts_marques . '\', points_contre = \'' . $pts_encaisses . '\', '
            . 'coeff_points = \'' . $coeff_points . '\', difference = \'' . $difference . '\' WHERE id_equipe = \'' . $id_equipe . '\' AND division = \'' . $division . '\' AND code_competition = \'' . $compet . '\'';

    $reqmaj = mysql_query($sqlmaj) or die('Erreur SQL !<br>' . $sqlmaj . '<br>' . mysql_error());
//addSqlActivity($sqlmaj);
}

function getMatchesWonWith5PlayersCount($idTeam, $codeCompetition) {
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_dom = \'' . $idTeam . '\' AND code_competition = \'' . $codeCompetition . '\' AND gagnea5_dom = \'1\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $gagnea5_dom = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_ext = \'' . $idTeam . '\' AND code_competition = \'' . $codeCompetition . '\' AND gagnea5_ext = \'1\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $gagnea5_ext = $data[0];
    }
    return $gagnea5_dom + $gagnea5_ext;
}

function getMatchesLostByForfeitCount($idTeam, $codeCompetition) {
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_dom = \'' . $idTeam . '\' AND code_competition = \'' . $codeCompetition . '\' AND forfait_dom = \'1\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $forfait_dom = $data[0];
    }
    $sql = 'SELECT COUNT(*) FROM matches WHERE id_equipe_ext = \'' . $idTeam . '\' AND code_competition = \'' . $codeCompetition . '\' AND forfait_ext = \'1\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $forfait_ext = $data[0];
    }
    return $forfait_ext + $forfait_dom;
}

function getClassement($compet, $div) {
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
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    $rang = 1;
    while ($data = mysql_fetch_assoc($req)) {
        $data['rang'] = $rang;
        $data['matches_won_with_5_players_count'] = getMatchesWonWith5PlayersCount($data['id_equipe'], $data['code_competition']);
        $data['matches_lost_by_forfeit_count'] = getMatchesLostByForfeitCount($data['id_equipe'], $data['code_competition']);
        $results[] = $data;
        $rang++;
    }
    return json_encode($results);
}

function getTeamRank($competition, $league, $idTeam) {
    $results = json_decode(getClassement($competition, $league), true);
    foreach ($results as $data) {
        if ($data['id_equipe'] === $idTeam) {
            return $data['rang'];
        }
    }
    return '';
}

function ajouterPenalite($compet, $id_equipe) {
    conn_db();
    $sql = 'SELECT penalite,division FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    if (mysql_num_rows($req) == 1) {
        $data = mysql_fetch_assoc($req);
        $penalite = $data['penalite'];
        $division = $data['division'];
    }
    $penalite++;
    $sqlmaj = 'UPDATE classements set penalite = \'' . $penalite . '\' WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req2 = mysql_query($sqlmaj);
    if ($req2 === FALSE) {
        return false;
    }
    calcul_classement($id_equipe, $compet, $division);
    mysql_close();
    addActivity("Une penalite a ete infligee a l'equipe " . getTeamName($id_equipe));
    return true;
}

function enleverPenalite($compet, $id_equipe) {
    conn_db();
    $sql = 'SELECT penalite,division FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    if (mysql_num_rows($req) == 1) {
        $data = mysql_fetch_assoc($req);
        $penalite = $data['penalite'];
        $division = $data['division'];
    }
    $penalite--;
    if ($penalite < 0) {
        $penalite = 0;
    }
    $sqlmaj = 'UPDATE classements set penalite = \'' . $penalite . '\' WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req2 = mysql_query($sqlmaj);
    if ($req2 === FALSE) {
        return false;
    }
    calcul_classement($id_equipe, $compet, $division);
    mysql_close();
    addActivity("Une penalite a ete annulee pour l'equipe " . getTeamName($id_equipe));
    return true;
}

function supprimerEquipeCompetition($compet, $id_equipe) {
    conn_db();
    $sql = 'DELETE FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = 'DELETE FROM matches WHERE (id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\') OR (id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\')';
    $req2 = mysql_query($sql);
    if ($req2 === FALSE) {
        return false;
    }
    mysql_close();
    addActivity("L'equipe " . getTeamName($id_equipe) . " a ete supprimee de la competition " . getTournamentName($compet));
    return true;
}

function certifierMatch($code_match) {
    conn_db();
    $sql = 'UPDATE matches SET certif = 1 WHERE code_match = \'' . $code_match . '\'';
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
    addActivity("Le match $code_match a ete certifie");
    return true;
}

function modifierMatch($code_match) {
    conn_db();
    $score_equipe_dom = filter_input(INPUT_POST, 'score_equipe_dom');
    $score_equipe_ext = filter_input(INPUT_POST, 'score_equipe_ext');
    $set_1_dom = filter_input(INPUT_POST, 'set_1_dom');
    $set_2_dom = filter_input(INPUT_POST, 'set_2_dom');
    $set_3_dom = filter_input(INPUT_POST, 'set_3_dom');
    $set_4_dom = filter_input(INPUT_POST, 'set_4_dom');
    $set_5_dom = filter_input(INPUT_POST, 'set_5_dom');
    $gagnea5_dom = filter_input(INPUT_POST, 'gagnea5_dom');
    $set_1_ext = filter_input(INPUT_POST, 'set_1_ext');
    $set_2_ext = filter_input(INPUT_POST, 'set_2_ext');
    $set_3_ext = filter_input(INPUT_POST, 'set_3_ext');
    $set_4_ext = filter_input(INPUT_POST, 'set_4_ext');
    $set_5_ext = filter_input(INPUT_POST, 'set_5_ext');
    $gagnea5_ext = filter_input(INPUT_POST, 'gagnea5_ext');
    $code_match = filter_input(INPUT_POST, 'code_match');
    $compet = filter_input(INPUT_POST, 'code_competition');
    $division = filter_input(INPUT_POST, 'division');
    $heure_reception = filter_input(INPUT_POST, 'heure_reception');
    $date_reception = filter_input(INPUT_POST, 'date_reception');
    $date_originale = filter_input(INPUT_POST, 'date_originale');
    $id_equipe_dom = filter_input(INPUT_POST, 'id_equipe_dom');
    $id_equipe_ext = filter_input(INPUT_POST, 'id_equipe_ext');
    if ($gagnea5_dom === null) {
        $gagnea5_dom = 0;
    }
    if ($gagnea5_dom === 'on') {
        $gagnea5_dom = 1;
    }
    if ($gagnea5_ext === null) {
        $gagnea5_ext = 0;
    }
    if ($gagnea5_ext === 'on') {
        $gagnea5_ext = 1;
    }
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
            . "gagnea5_dom = '$gagnea5_dom', "
            . "gagnea5_ext = '$gagnea5_ext', "
            . "forfait_dom = '$forfait_dom', "
            . "forfait_ext = '$forfait_ext', "
            . "date_reception = DATE(STR_TO_DATE('$date_reception', '%d/%m/%Y')), "
            . "heure_reception = '$heure_reception', "
            . "report = '$report' "
            . "WHERE code_match = '$code_match'";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    calcul_classement($id_equipe_dom, $compet, $division);
    calcul_classement($id_equipe_ext, $compet, $division);
    mysql_close();
    addActivity("Le match $code_match a ete modifie");
    return true;
}

function addActivity($comment) {
    conn_db();
    $sessionIdEquipe = $_SESSION['id_equipe'];
    if ($sessionIdEquipe === "admin") {
        $sessionIdEquipe = 999;
    }
    $sql = "INSERT activity SET comment=\"$comment\", activity_date=NOW(), user_id=$sessionIdEquipe";
    mysql_query($sql);
    mysql_close();
    return;
}

function modifierMonEquipe() {
    conn_db();
    $id_equipe = filter_input(INPUT_POST, 'id_equipe');
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    if ($sessionIdEquipe != $id_equipe) {
        return false;
    }
    $id_club = filter_input(INPUT_POST, 'id_club');
    $responsable = filter_input(INPUT_POST, 'responsable');
    $telephone_1 = filter_input(INPUT_POST, 'telephone_1');
    $telephone_2 = filter_input(INPUT_POST, 'telephone_2');
    $email = filter_input(INPUT_POST, 'email');
    $gymnase = filter_input(INPUT_POST, 'gymnase');
    $localisation = filter_input(INPUT_POST, 'localisation');
    $jour_reception = filter_input(INPUT_POST, 'jour_reception');
    $heure_reception = filter_input(INPUT_POST, 'heure_reception');
    $site_web = filter_input(INPUT_POST, 'site_web');
    $sql = "UPDATE details_equipes SET "
            . "responsable='$responsable', "
            . "telephone_1='$telephone_1', "
            . "telephone_2='$telephone_2', "
            . "email='$email', "
            . "gymnase='$gymnase', "
            . "localisation='$localisation', "
            . "jour_reception='$jour_reception', "
            . "heure_reception='$heure_reception', "
            . "site_web='$site_web' "
            . "WHERE id_equipe=$id_equipe";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "UPDATE equipes SET "
            . "id_club=$id_club "
            . "WHERE id_equipe=$id_equipe";
    $req = mysql_query($sql);
    mysql_close();
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

function modifierMonMotDePasse() {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $password = filter_input(INPUT_POST, 'password');
    $sql = "UPDATE comptes_acces SET "
            . "password='$password' "
            . "WHERE id_equipe=$sessionIdEquipe";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
    addActivity("Mot de passe modifie");
    return true;
}

function supprimerMatch($code_match) {
    conn_db();
    $sql = 'DELETE FROM matches WHERE code_match = \'' . $code_match . '\'';
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
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
        if (!setRetard($data['code_match'], 2)) {
            return false;
        }
        return envoi_mail($data['id_equipe_dom'], $data['id_equipe_ext'], $data['code_competition'], $data['date_reception']);
    }
    $computedDate->sub($fifteenDays);
    $computedDate->add($tenDays);
    if ($currentDate > $computedDate) {
        if (intval($data['retard']) == 1) {
            return true;
        }
        if (!setRetard($data['code_match'], 1)) {
            return false;
        }
        return envoi_mail($data['id_equipe_dom'], $data['id_equipe_ext'], $data['code_competition'], $data['date_reception']);
    }
    return setRetard($data['code_match'], 0);
}

function setRetard($code_match, $valeur) {
    conn_db();
    $sql = "UPDATE matches SET retard = $valeur WHERE code_match = '$code_match'";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
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
        de1.fdm AS fdm_dom,
        e1.nom_equipe AS equipe_dom,
        m.id_equipe_ext,
        de2.fdm AS fdm_ext,
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
        m.gagnea5_dom+0 AS gagnea5_dom,
        m.gagnea5_ext+0 AS gagnea5_ext,
        m.forfait_dom+0 AS forfait_dom,
        m.forfait_ext+0 AS forfait_ext,
        m.certif+0 AS certif,
        m.report+0 AS report,
        m.retard+0 AS retard
        FROM matches m 
        JOIN competitions c ON c.code_competition = m.code_competition
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        JOIN details_equipes de1 ON de1.id_equipe = m.id_equipe_dom
        JOIN details_equipes de2 ON de2.id_equipe = m.id_equipe_ext
        JOIN journees j ON j.numero=m.journee AND j.code_competition=m.code_competition " . $whereClause . " " . $orderClause;
}

function getMatches($compet, $div) {
    conn_db();
    $sql = getSqlSelectMatches("WHERE m.code_competition = '$compet' AND m.division = '$div'", "ORDER BY m.date_reception, m.code_match");
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
        if ((intval($data['score_equipe_dom']) == 0) && (intval($data['score_equipe_ext']) == 0)) {
            checkNotifyUpdateReport($data);
        } else {
            setRetard($data['code_match'], 0);
        }
    }
    return json_encode($results);
}

function getMyMatches() {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = getSqlSelectMatches("WHERE m.id_equipe_dom = $sessionIdEquipe OR m.id_equipe_ext = $sessionIdEquipe", "ORDER BY m.date_reception, m.code_match");
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getMonEquipe() {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT 
        e.code_competition, 
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')') AS team_full_name,
        e.id_club,
        c.nom AS club,
        d.id_equipe,
        d.responsable,
        d.telephone_1,
        d.telephone_2,
        d.email,
        d.gymnase,
        d.localisation,
        d.jour_reception,
        d.heure_reception,
        d.site_web,
        d.photo,
        d.fdm
        FROM details_equipes d
        LEFT JOIN equipes e ON e.id_equipe=d.id_equipe
        LEFT JOIN clubs c ON c.id=e.id_club
        LEFT JOIN competitions comp ON comp.code_competition=e.code_competition
        WHERE d.id_equipe = $sessionIdEquipe";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getMyPlayers($rootPath = '../', $doHideInactivePlayers = false) {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT CONCAT(j.nom, ' ', j.prenom, ' (', j.num_licence, ')') AS full_name, CONCAT(UPPER(LEFT(j.prenom, 1)), LOWER(SUBSTRING(j.prenom, 2))) AS prenom, UPPER(j.nom) AS nom, j.telephone, j.email, j.num_licence, CONCAT('images/joueurs/', UPPER(REPLACE(j.nom, '-', '')), UPPER(LEFT(j.prenom, 1)), LOWER(SUBSTRING(REPLACE(j.prenom, '-', ''),2)), '.jpg') AS path_photo, j.sexe, j.departement_affiliation, j.est_actif+0 AS est_actif, j.id_club, j.telephone2, j.email2, 
        CASE 
            WHEN (DATEDIFF(j.date_homologation, CONCAT(YEAR(j.date_homologation), '-08-31')) > 0) THEN 
                CASE 
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation)+1, '-08-31'),CURDATE()) > 0) THEN 1
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation)+1, '-08-31'), CURDATE()) <= 0) THEN 0
                END
            WHEN (DATEDIFF(j.date_homologation, CONCAT(YEAR(j.date_homologation), '-08-31')) <= 0) THEN 
                CASE 
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation), '-08-31'),CURDATE()) > 0) THEN 1
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation), '-08-31'), CURDATE()) <= 0) THEN 0
                END         
        END AS est_licence_valide, 
        j.est_responsable_club+0 AS est_responsable_club, 
        je.is_captain+0 AS is_captain, 
        je.is_vice_leader+0 AS is_vice_leader, 
        je.is_leader+0 AS is_leader, 
        j.id, j.date_homologation, j.show_photo+0 AS show_photo 
        FROM joueur_equipe je
        LEFT JOIN joueurs j ON j.id=je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe";
    if ($doHideInactivePlayers) {
        $sql .= " AND j.est_actif+0=1 ";
    }
    $sql .= " ORDER BY sexe, nom ASC";

    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $index => $result) {
        if ($result['show_photo'] === '1') {
            if (file_exists($rootPath . $result['path_photo']) === FALSE) {
                switch ($result['sexe']) {
                    case 'M':
                        $results[$index]['path_photo'] = 'images/joueurs/MaleMissingPhoto.png';
                        break;
                    case 'F':
                        $results[$index]['path_photo'] = 'images/joueurs/FemaleMissingPhoto.png';
                        break;
                    default:
                        break;
                }
            }
        } else {
            switch ($result['sexe']) {
                case 'M':
                    $results[$index]['path_photo'] = 'images/joueurs/MalePhotoNotAllowed.png';
                    break;
                case 'F':
                    $results[$index]['path_photo'] = 'images/joueurs/FemalePhotoNotAllowed.png';
                    break;
                default:
                    break;
            }
        }
    }
    return json_encode($results);
}

function getMyPreferences() {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT r.registry_value AS is_remind_matches FROM registry r
        WHERE r.registry_key = 'users.$sessionIdEquipe.is_remind_matches'";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function saveMyPreferences() {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
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
    $req = mysql_query($sql);
    mysql_close();
    if ($req === FALSE) {
        return false;
    }
    return true;
}

function isRegistryKeyPresent($key) {
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM registry WHERE registry_key = '$key'";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function getMyPlayersPdf() {
    return getMyPlayers('', true);
}

function getPlayers() {
    conn_db();
    $sql = "SELECT 
        CONCAT(j.nom, ' ', j.prenom, ' (', j.num_licence, ')') AS full_name,
        j.prenom, 
        j.nom, 
        j.telephone, 
        j.email, 
        j.num_licence,
        CONCAT('images/joueurs/', UPPER(REPLACE(j.nom, '-', '')), UPPER(LEFT(j.prenom, 1)), LOWER(SUBSTRING(REPLACE(j.prenom, '-', ''),2)), '.jpg') AS path_photo,
        j.sexe, 
        j.departement_affiliation, 
        j.est_actif+0 AS est_actif, 
        j.id_club, 
        c.nom AS club, 
        j.telephone2, 
        j.email2, 
        CASE 
            WHEN (DATEDIFF(j.date_homologation, CONCAT(YEAR(j.date_homologation), '-08-31')) > 0) THEN 
                CASE 
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation)+1, '-08-31'),CURDATE()) > 0) THEN 1
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation)+1, '-08-31'), CURDATE()) <= 0) THEN 0
                END
            WHEN (DATEDIFF(j.date_homologation, CONCAT(YEAR(j.date_homologation), '-08-31')) <= 0) THEN 
                CASE 
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation), '-08-31'),CURDATE()) > 0) THEN 1
                    WHEN (DATEDIFF(CONCAT(YEAR(j.date_homologation), '-08-31'), CURDATE()) <= 0) THEN 0
                END         
        END AS est_licence_valide, 
        j.est_responsable_club+0 AS est_responsable_club, 
        j.id, 
        j.date_homologation,
        j.show_photo+0 AS show_photo 
        FROM joueurs j
        LEFT JOIN clubs c ON c.id = j.id_club";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $index => $result) {
        if ($result['show_photo'] === '1') {
            if (file_exists("../" . $result['path_photo']) === FALSE) {
                switch ($result['sexe']) {
                    case 'M':
                        $results[$index]['path_photo'] = 'images/joueurs/MaleMissingPhoto.png';
                        break;
                    case 'F':
                        $results[$index]['path_photo'] = 'images/joueurs/FemaleMissingPhoto.png';
                        break;
                    default:
                        break;
                }
            }
        } else {
            switch ($result['sexe']) {
                case 'M':
                    $results[$index]['path_photo'] = 'images/joueurs/MalePhotoNotAllowed.png';
                    break;
                case 'F':
                    $results[$index]['path_photo'] = 'images/joueurs/FemalePhotoNotAllowed.png';
                    break;
                default:
                    break;
            }
        }
    }
    foreach ($results as $index => $result) {
        $results[$index]['team_leader_list'] = getTeamsListForCaptain($results[$index]['id']);
        $results[$index]['teams_list'] = getTeamsList($results[$index]['id']);
    }
    return json_encode($results);
}

function getTeamsListForCaptain($playerId) {
    $teams = array();
    conn_db();
    $sql = "SELECT CONCAT(e.nom_equipe, '(',e.code_competition,')') AS team FROM joueur_equipe je JOIN equipes e ON e.id_equipe=je.id_equipe
    WHERE je.id_joueur = $playerId AND is_captain+0=1";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $teams[] = $data['team'];
    }
    return implode(',', $teams);
}

function getTeamsList($playerId) {
    $teams = array();
    conn_db();
    $sql = "SELECT CONCAT(e.nom_equipe, '(',e.code_competition,')') AS team FROM joueur_equipe je JOIN equipes e ON e.id_equipe=je.id_equipe
    WHERE je.id_joueur = $playerId";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $teams[] = $data['team'];
    }
    return implode(',', $teams);
}

function isPlayerInTeam($idPlayer, $idTeam) {
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_joueur = $idPlayer AND id_equipe = $idTeam";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function updateMyTeamCaptain($idPlayer) {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (!isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_captain = 0 WHERE id_equipe = $idTeam";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_captain = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau capitaine : " . getPlayerFullName($idPlayer));
    return true;
}

function updateMyTeamViceLeader($idPlayer) {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (!isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_vice_leader = 0 WHERE id_equipe = $idTeam";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_vice_leader = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau suppleant : " . getPlayerFullName($idPlayer));
    return true;
}

function updateMyTeamLeader($idPlayer) {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (!isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_leader = 0 WHERE id_equipe = $idTeam";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    $sql = "UPDATE joueur_equipe SET is_leader = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
    addActivity("L'equipe " . getTeamName($idTeam) . " a un nouveau responsable : " . getPlayerFullName($idPlayer));
    return true;
}

function addPlayerToMyTeam($idPlayer) {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "INSERT joueur_equipe SET id_joueur = $idPlayer, id_equipe = $idTeam";
    $req = mysql_query($sql);
    mysql_close();
    if ($req === FALSE) {
        return false;
    }
    addActivity("Ajout de " . getPlayerFullName($idPlayer) . " a l'equipe " . getTeamName($idTeam));
    return true;
}

function addPlayerToTeam($idPlayer, $idTeam) {
    conn_db();
    if (isPlayerInTeam($idPlayer, $idTeam)) {
        return true;
    }
    $sql = "INSERT joueur_equipe SET id_joueur = $idPlayer, id_equipe = $idTeam";
    $req = mysql_query($sql);
    mysql_close();
    if ($req === FALSE) {
        return false;
    }
    addActivity("Ajout de " . getPlayerFullName($idPlayer) . " a l'equipe " . getTeamName($idTeam));
    return true;
}

function getPlayerFullName($idPlayer) {
    conn_db();
    $sql = "SELECT 
        CONCAT(j.nom, ' ', j.prenom, ' (', j.num_licence, ')') AS player_full_name
        FROM joueurs j
        WHERE j.id = $idPlayer";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysql_close();
    return $results[0]['player_full_name'];
}

function getTeamName($idTeam) {
    if ($idTeam === 0) {
        return 'Non renseigné';
    }
    conn_db();
    $sql = "SELECT 
        CONCAT(e.nom_equipe, '(',e.code_competition,')') AS team_name 
        FROM equipes e 
        WHERE e.id_equipe = $idTeam";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysql_close();
    return $results[0]['team_name'];
}

function getTournamentName($tournamentCode) {
    conn_db();
    $sql = "SELECT 
        c.libelle AS tournament_name
        FROM competitions c 
        WHERE c.code_competition = '$tournamentCode'";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysql_close();
    return $results[0]['tournament_name'];
}

function getClubName($idClub) {
    conn_db();
    $sql = "SELECT 
        c.nom as club_name 
        FROM clubs c 
        WHERE c.id = $idClub";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysql_close();
    return $results[0]['club_name'];
}

function addPlayersToClub($idPlayers, $idClub) {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] !== "admin") {
        return false;
    }
    $sql = "UPDATE joueurs SET id_club = $idClub WHERE id IN ($idPlayers)";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
    foreach (explode(',', $idPlayers) as $idPlayer) {
        addActivity(getPlayerFullName($idPlayer) . " a ete ajoute au club " . getClubName($idClub));
    }
    return true;
}

function getIdClubFromIdTeam($idTeam) {
    conn_db();
    $sql = "SELECT 
        e.id_club
        FROM equipes e 
        WHERE e.id_equipe = $idTeam";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysql_close();
    return $results[0]['id_club'];
}

function addPlayersToTeam($idPlayers, $idTeam) {
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] !== "admin") {
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
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $idTeam = $_SESSION['id_equipe'];
    if (!isPlayerInTeam($idPlayer, $idTeam)) {
        return false;
    }
    $sql = "DELETE FROM joueur_equipe WHERE id_joueur = $idPlayer AND id_equipe = $idTeam";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
    mysql_close();
    addActivity(getPlayerFullName($idPlayer) . " a ete supprime de l'equipe " . getTeamName($idTeam));
    return true;
}

function getMyTeamSheet() {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT 
        c.nom AS club,
        comp.code_competition AS code_competition,
        comp.libelle AS championnat,
        cla.division,
        CONCAT(j.prenom, ' ', j.nom) AS leader,
        j.telephone AS portable,
        j.email AS courriel,
        CONCAT(de.jour_reception, ' ', de.heure_reception) AS creneau,
        de.gymnase,
        e.nom_equipe AS equipe,
        DATE_FORMAT(NOW(), '%d/%m/%Y') AS date_visa_ctsd
        FROM equipes e
        JOIN clubs c ON c.id = e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        JOIN classements cla ON cla.code_competition=e.code_competition AND cla.id_equipe=e.id_equipe
        JOIN joueur_equipe je ON je.id_equipe=e.id_equipe
        JOIN joueurs j ON j.id=je.id_joueur
        JOIN details_equipes de ON de.id_equipe=e.id_equipe
        WHERE je.is_leader=1
        AND je.id_equipe = $sessionIdEquipe";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function savePhoto($lastName, $firstName) {
    if (empty($_FILES['photo']['name'])) {
        return true;
    }
    $uploaddir = '../images/joueurs/';
    $uploadfile = $uploaddir . strtoupper(str_replace('-', '', $lastName)) . ucwords(str_replace('-', '', $firstName)) . '.jpg';
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadfile)) {
        addActivity("Une nouvelle photo a ete transmise pour le joueur $firstName $lastName");
        return true;
    }
    return false;
}

function isPlayerExists($licenceNumber) {
    conn_db();
    $sql = "SELECT COUNT(*) AS cnt FROM joueurs WHERE num_licence = '$licenceNumber'";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    if (intval($results[0]['cnt']) === 0) {
        return false;
    }
    return true;
}

function savePlayer() {
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
        'telephone2' => filter_input(INPUT_POST, 'telephone2'),
        'email2' => filter_input(INPUT_POST, 'email2'),
        'est_responsable_club' => filter_input(INPUT_POST, 'est_responsable_club'),
        'id' => filter_input(INPUT_POST, 'id'),
        'date_homologation' => filter_input(INPUT_POST, 'date_homologation'),
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
            case 'date_homologation':
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
    $req = mysql_query($sql);
    mysql_close();
    if ($req === FALSE) {
        return false;
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
    return savePhoto($inputs['nom'], $inputs['prenom']);
}
