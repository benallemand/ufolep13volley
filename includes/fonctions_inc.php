<?php

session_start();

function getLastResults() {
    conn_db();
    /** Format UTF8 pour afficher correctement les accents */
    $sql = "select 
    c.libelle AS competition, 
    IF(c.code_competition='f' OR c.code_competition='m', CONCAT('Division ', m.division, ' - ', j.nommage), CONCAT('Poule ', m.division, ' - ', j.nommage)) AS division_journee, 
    c.code_competition AS code_competition,
    m.division AS division,
    e1.nom_equipe AS equipe_domicile,
    m.score_equipe_dom+0 AS score_equipe_dom, 
    m.score_equipe_ext+0 AS score_equipe_ext, 
    e2.nom_equipe AS equipe_exterieur, 
    CONCAT(m.set_1_dom, '-', set_1_ext) AS set1, 
    CONCAT(m.set_2_dom, '-', set_2_ext) AS set2, 
    CONCAT(m.set_3_dom, '-', set_3_ext) AS set3, 
    CONCAT(m.set_4_dom, '-', set_4_ext) AS set4, 
    CONCAT(m.set_5_dom, '-', set_5_ext) AS set5, 
    m.date_reception
    from matches m
    left join journees j on j.numero=m.journee and j.code_competition=m.code_competition
    left join competitions c on c.code_competition =  m.code_competition
    left join equipes e1 on e1.id_equipe =  m.id_equipe_dom
    left join equipes e2 on e2.id_equipe =  m.id_equipe_ext
    where m.score_equipe_dom!=0 OR m.score_equipe_ext!=0
    order by date_reception DESC";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        if ($data['code_competition'] === 'm') {
            $data['url'] = 'champ_masc.php?d=' . $data['division'];
        } else if ($data['code_competition'] === 'f') {
            $data['url'] = 'champ_fem.php?d=' . $data['division'];
        } else if ($data['code_competition'] === 'kh') {
            $data['url'] = 'coupe_kh.php?d=' . $data['division'];
        } else if ($data['code_competition'] === 'c') {
            $data['url'] = 'coupe.php?d=' . $data['division'];
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
function conn_db()
//************************************************************************************************
/*
 * * Fonction    : conn_db
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : connecte la base sql
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 18/04/2010
 */ {
//D�claration des constantes
    if (($_SERVER['SERVER_NAME'] === 'localhost') ||
            ($_SERVER['SERVER_NAME'] === '192.168.0.4') ||
            ($_SERVER['SERVER_NAME'] === '82.228.19.67')) {
        $server = "localhost";
        $user = "root";
        $password = "admin";
    } else {
        $server = "clustermysql05.hosteur.com";
        $user = "ufolep_volley";
        $password = "vietvod@o";
    }
    $base = "ufolep_13volley";

// on se connecte � MySQL 
    $db = mysql_connect($server, $user, $password);
    mysql_select_db($base, $db);
    if (($_SERVER['SERVER_NAME'] !== 'localhost') && ($_SERVER['SERVER_NAME'] !== '82.228.19.67')) {
        mysql_query("SET NAMES UTF8");
    }
}

//************************************************************************************************
//************************************************************************************************
function recup_nom_equipe($compet, $id)
//************************************************************************************************
/*
 * * Fonction    : recup_nom_equipe 
 * * Input       : STRING $id
 * * Output      : aucun 
 * * Description : R�cup�re le nom d'une �quipe
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
 * * Description : R�cup�re le mail d'une �quipe
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
function affich_infos($compet, $div)
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
 * * Description : R�cup�re la comp�tition maitre m ou f d'une �quipe
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

//************************************************************************************************
//************************************************************************************************
function recup_nom_compet($compet)
//************************************************************************************************
/*
 * * Fonction    : recup_nom_compet 
 * * Input       : STRING $compet
 * * Output      : aucun 
 * * Description : R�cup�re le nom de la comp�tition
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 07/04/2011
 */ {
//Connexion � la base
    conn_db();

    $sql = 'SELECT libelle FROM competitions WHERE code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
        return $data['libelle'];
    }
}

//************************************************************************************************
//************************************************************************************************
function date_fr($date)
//************************************************************************************************
/*
 * * Fonction    : date_fr
 * * Input       : STRING $date
 * * Output      : aucun 
 * * Description : Affiche les dates au bon format 
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 18/04/2010
 */ {
    $annee = substr($date, 0, 4);
    $mois = substr($date, 5, 2);
    $jour = substr($date, 8, 2);
    $date = $jour . '/' . $mois . '/' . $annee;
    return $date;
}

//************************************************************************************************
//************************************************************************************************
function date_uk($date)
//************************************************************************************************
/*
 * * Fonction    : date_uk
 * * Input       : STRING $date
 * * Output      : aucun 
 * * Description : Affiche les dates au bon format 
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 07/05/2010
 */ {
    $annee = substr($date, 6, 4);
    $mois = substr($date, 3, 2);
    $jour = substr($date, 0, 2);
    $date = $annee . '-' . $mois . '-' . $jour;
    return $date;
}

//************************************************************************************************
//************************************************************************************************
function cryptage($Texte, $Cle)
//************************************************************************************************
/*
 * * Fonction    : cryptage 
 * * Input       : $text
 * * Output      : expression crypt�e 
 * * Description : Crypte une expression
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 14/09/2011 
 */ {
    srand((double) microtime() * 1000000);
    $CleDEncryptage = md5(rand(0, 32000));
    $Compteur = 0;
    $VariableTemp = "";
    for ($Ctr = 0; $Ctr < strlen($Texte); $Ctr++) {
        if ($Compteur == strlen($CleDEncryptage))
            $Compteur = 0;
        $VariableTemp.= substr($CleDEncryptage, $Compteur, 1) . (substr($Texte, $Ctr, 1) ^ substr($CleDEncryptage, $Compteur, 1) );
        $Compteur++;
    }
    return base64_encode(GenerationCle($VariableTemp, $Cle));
}

//************************************************************************************************
//************************************************************************************************
//************************************************************************************************
function decryptage($Texte, $Cle)
//************************************************************************************************
/*
 * * Fonction    : decryptage 
 * * Input       : $text
 * * Output      : expression decrypt�e 
 * * Description : D�Crypte une expression
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 14/09/2011 
 */ {
    $Texte = GenerationCle(base64_decode($Texte), $Cle);
    $VariableTemp = "";
    for ($Ctr = 0; $Ctr < strlen($Texte); $Ctr++) {
        $md5 = substr($Texte, $Ctr, 1);
        $Ctr++;
        $VariableTemp.= (substr($Texte, $Ctr, 1) ^ $md5);
    }
    return $VariableTemp;
}

//************************************************************************************************
//************************************************************************************************
//************************************************************************************************
function GenerationCle($Texte, $CleDEncryptage)
//************************************************************************************************
/*
 * * Fonction    : decryptage 
 * * Input       : $text
 * * Output      : expression decrypt�e 
 * * Description : D�Crypte une expression
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 14/09/2011 
 */ {
    $CleDEncryptage = md5($CleDEncryptage);
    $Compteur = 0;
    $VariableTemp = "";
    for ($Ctr = 0; $Ctr < strlen($Texte); $Ctr++) {
        if ($Compteur == strlen($CleDEncryptage))
            $Compteur = 0;
        $VariableTemp.= substr($Texte, $Ctr, 1) ^ substr($CleDEncryptage, $Compteur, 1);
        $Compteur++;
    }
    return $VariableTemp;
}

//************************************************************************************************
//************************************************************************************************
//************************************************************************************************
function affich_commission()
//************************************************************************************************
/*
 * * Fonction    : affich_commission 
 * * Input       : aucun 
 * * Output      : aucun 
 * * Description : affichage de la composition de la commission
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 21/04/2010 
 */ {
//Connexion � la base
    conn_db();

// on r�cup�re les infos de la table SQL
    $sql = 'SELECT * FROM commission';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
// si aucune photo n'est pr�sente on affiche l'image inconnue
        if ($data['photo'] == "") {
            $photo = "images/port.inconnu.jpg";
        } else {
            $photo = $data['photo'];
        }

//echo'<div class="membre'.$data['id_commission'].'">';
        echo'<div class="membre">';
        echo'  <div class="photo"><img src="' . $photo . '"></div>';
        echo'  <div class="details">';
        echo'    <h1>' . $data['prenom'] . ' ' . $data['nom'] . '</h1>';
        echo'    <ul>';
        echo'      <li class="fonction">' . $data['fonction'] . '</li>';
        echo'      <li><span>' . $data['telephone1'] . '</span></li>';
        echo'      <li><span>' . $data['telephone2'] . '</span></li>';
        echo'      <li><span><a href="mailto:' . $data['email'] . '" target="_blank">' . $data['email'] . '</a></span></li>';
        echo'    </ul>';
        echo'  </div>';
        echo'</div>';
    }
// Fermeture de la connexion � mysql 
//mysql_close(); 
}

//************************************************************************************************
//************************************************************************************************

function affich_annuaire()

//************************************************************************************************
/*
 * * Fonction    : affich_annuaire 
 * * Input       : aucun 
 * * Output      : aucun 
 * * Description : affichage de l'annuaire des �quipes
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 22/04/2010 
 */ {
//Connexion � la base
    conn_db();

// on r�cup�re les competitions dans la table equipes
    $sql = 'SELECT DISTINCT(code_competition) FROM equipes';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
        $code_competition = $data['code_competition'];
// pour chaque enregistrement trouv� on r�cup�re le libell�
        $sql_libelle = 'SELECT libelle FROM competitions WHERE code_competition = \'' . $code_competition . '\'';
        $req_libelle = mysql_query($sql_libelle) or die('Erreur SQL !<br>' . $sql_libelle . '<br>' . mysql_error());
        $data_libelle = mysql_fetch_assoc($req_libelle);
        $libelle_compet = $data_libelle['libelle'];

        echo'<div class="competition">';
        echo'	<h1>' . $libelle_compet . '</h1>';

// pour chaque comp�tition on r�cup�re les divisions
        $sql_division = 'SELECT DISTINCT(division) FROM classements WHERE code_competition = \'' . $code_competition . '\'';
        $req_division = mysql_query($sql_division) or die('Erreur SQL !<br>' . $sql_division . '<br>' . mysql_error());
        while ($data_division = mysql_fetch_assoc($req_division)) {
            if (mysql_num_rows($req_division) < 2) {
                $division = $data_division['division'];
                $nom_division = "Unique";
            } else {
                $division = $data_division['division'];
                $nom_division = $data_division['division'];
            }

            echo'		<div class="float">';
            echo'  		<h2>Division ' . $nom_division . '</h2>';
            echo'  		<ul>';

// pour chaque division on cherche les �quipes et on les affiche
            $sql_equipe = 'SELECT equipes.nom_equipe, equipes.id_equipe FROM classements, equipes WHERE classements.code_competition = \'' . $code_competition . '\' AND classements.division = \'' . $division . '\' AND classements.id_equipe = equipes.id_equipe';
            $req_equipe = mysql_query($sql_equipe) or die('Erreur SQL !<br>' . $sql_equipe . '<br>' . mysql_error());
            while ($data_equipe = mysql_fetch_assoc($req_equipe)) {
                $nom_equipe = $data_equipe['nom_equipe'];
                $id_equipe = $data_equipe['id_equipe'];
                echo'			<li><a href="annuaire.php?id=' . $id_equipe . '&c=' . $code_competition . '" target="_self">' . $nom_equipe . '</a></li>';
            }

            echo'  		</ul>';
            echo'		</div>';
        }
        echo'</div>';
        echo'<div id="flux">';
    }
// Fermeture de la connexion � mysql 
//mysql_close(); 
}

function getPlayersFromTeam($id_equipe) {
    $players = array();
    conn_db();
    $sql = "select CONCAT(j.prenom, ' ', j.nom) AS player from joueur_equipe je
    left join joueurs j on j.id = je.id_joueur
    where id_equipe = $id_equipe";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $players[] = $data['player'];
    }
    return $players;
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
 * * Description : Affiche les d�tails d'une �quipe 
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 23/04/2010 
 */ {
//Connexion � la base
    conn_db();

// on ex�cute la requ�te
    $sql = 'SELECT * FROM `details_equipes`  WHERE `id_equipe` = \'' . $id_equipe . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
//on r�cup�re les donn�es et on les affecte
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

//on affiche les donn�es
        echo '  <div class="photo_equipe">';
        echo '<img src="' . $photo . '" width="300" height="200">';
        if ($_SESSION['id_equipe'] === $id_equipe) {
            echo '<br/><a href="mailto:photos@ufolep13volley.org" target="_blank">Envoyer une photo d\'�quipe</a>';
        }
        echo '</div>';
        echo'  <div class="infos_equipe">';
        echo'    <h1>' . $nom_equipe . ' - Vos d�tails</h1>';
        echo'    <table>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Responsable :</td>';
        echo'		<td class="datas_details"><img src="ajax/getImageFromText.php?text=' . base64_encode(utf8_encode($responsable)) . '"/><td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">T�l�phone 1 :</td>';
        echo'		<td class="datas_details"><img src="ajax/getImageFromText.php?text=' . base64_encode(utf8_encode($telephone_1)) . '"/><td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">T�l�phone 2 :</td>';
        echo'		<td class="datas_details"><img src="ajax/getImageFromText.php?text=' . base64_encode(utf8_encode($telephone_2)) . '"/><td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Email :</td>';
        echo'		<td class="datas_details"><img src="ajax/getImageFromText.php?text=' . base64_encode(utf8_encode($email)) . '"/><td>';
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">R�ception le :</td>';
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
                echo'		<td class="datas_details">T�l�chargement Non Autoris�<td>';
            }
        } else {
            echo'		<td class="datas_details">Fiche Equipe Non Cr��e !!! <td>';
        }
        echo'	  </tr>';
        echo'    </table>';
        echo'  </div>';
    }

    echo'<div id="flux"></div>';
}

//************************************************************************************************
//************************************************************************************************
function affich_formulaire($err)
//************************************************************************************************
/*
 * * Fonction    : affich_formulaire
 * * Input       : $err code d'erreur si echec de l'authentification
 * * Output      : aucun 
 * * Description : Affiche le formulaire d'authentification 
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 27/04/2010 
 */ {
    echo'<div id="login">';
    echo'  <form id="login-form" action="includes/traitement.php?a=auth" method="post">';
    echo'    <div id="login-first">';
    echo'    <p><span>Login</span><input id="login-name" class="input-mini" name="login" value="" title="Login" type="text" /></p>';
    echo'    <p><span>Mot de passe</span><input id="login-pass" class="input-mini" name="password" value="" title="Mot de passe" type="password" /></p>';
    echo'    <p>' . $err . '</p>';
    echo'    </div>';
    echo'    <div id="login-second">';
    echo'    <input value="Connexion" name="login-submit" id="login-submit" class="submit" type="submit" />';
    echo'    </div>';
    echo'  </form>';
    echo'</div> <!-- login -->';
}

//************************************************************************************************
//************************************************************************************************
function affich_connecte()
//************************************************************************************************
/*
 * * Fonction    : affich_connecte
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : Affiche le nom de l'�quipe connect�
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
        echo'<li class="admin">Connect� : <span class="grouge">' . $nom_equipe . '</span>';
        echo' | ';
        echo'<span><a href="includes/traitement.php?a=deconn">Se d�connecter</a></span></li>';
        echo'</ul>';
        echo'</div>';
    }
}

//************************************************************************************************
//************************************************************************************************
function affich_admin_site()
//************************************************************************************************
/*
 * * Fonction    : affich_admin
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : Affiche le calque admin
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 10/05/2010 
 */ {
// On affiche le div si on est connect� en admin
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        echo'<div id="bottom">';
        echo'  <ul>';
        echo'    <li>';
        echo'      <span><img src="images/logo_equipe.png" title="Nouvelle �quipe" alt="Nouvelle �quipe"/></span>';
        echo'      <span><img src="images/logo_equipe_s.png" title="Supprimer une �quipe" alt="Supprimer une �quipe" /></span>';
        echo'      <span><img src="images/n_compet.png" title="Nouvelle comp�tition" alt="Nouvelle comp�tition"/></span>';
        echo'      <span><img src="images/s_compet.png" title="Supprimer une comp�tition" alt="Supprimer une comp�tition"/></span>';
        echo'    </li>';
        echo'  </ul>';
        echo'</div>';
    }
}

//************************************************************************************************
//************************************************************************************************
function affich_admin_page($compet)
//************************************************************************************************
/*
 * * Fonction    : affich_admin_page
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : Affiche le calque admin page
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 10/05/2010 
 */ {
// On affiche le div si on est connect� en admin
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        echo'<div id="admin_page">';
        echo'  <ul>';
        echo'    <li><span><a href="includes/traitement.php?a=ie&c=' . $compet . '" target="_self"><img src="images/ajout.gif" title="Inscrire une �quipe" alt="Inscrire �quipe"/></span><span>Inscrire une �quipe</span></li>';
        echo'  </ul>';
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

    $message = '<html><head><title>Saisie Internet des r�sultats</title></head><body>';
    $message = $message . 'Aux �quipes de ' . recup_nom_equipe($compet, $id1) . ' et ' . recup_nom_equipe($compet, $id2) . '<BR>';
    $message = $message . 'Comme vous avez d� le lire sur le r�glement, la saisie des informations sur le site internet doit �tre rigoureuse (pour le suivi de la commission Volley et pour l\'int�r�t qu\'y portent les joueurs)<BR><BR>';
    $message = $message . 'Pour r�sumer, sur le site, 10 jours apr�s la date indiqu�e pour le match (qui peut �tre en rouge si le match a �t� report�e), il doit y avoir un r�sultat affich�.<BR><BR>';
    $message = $message . 'Pour votre match du <b>' . $matchDate->format('d/m/Y') . '</b> cela n\'est pas le cas. Puisqu\'il s\'agit d\'un premier message d\'alerte, nous vous donnons un d�lai suppl�mentaire de 5 jours pour que :<BR>';
    $message = $message . '- soit le r�sultat soit indiqu�<BR>';
    $message = $message . '- soit une autre date de match soit affich�e (pour cela il faut me la communiquer en tant que responsable des classements)<BR><BR>';
    $message = $message . 'Je vous rappelle que les deux �quipes doivent veiller � ce que cette r�gle soit suivie ; les deux pourraient donc �tre p�nalis�es.<BR><BR>';
    $message = $message . 'Cordialement<BR><BR>Laurent Gorlier<BR>Responsable des classements<BR>';
    $message = $message . '</body></html>';

    $dest = recup_mail_equipe($id1) . "," . recup_mail_equipe($id2);

    return mail($dest, "[Ufolep 13 Volley] Saisie Internet des r�sultats", $message, $headers);
}

//************************************************************************************************
//************************************************************************************************
function affich_portail_equipe($id)
//************************************************************************************************
/*
 * * Fonction    : affich_portail_equipe
 * * Input       : STRING $id, id de l'�quipe
 * * Output      : aucun 
 * * Description : Affiche le portail de l'�quipe qui vient de se connecter.
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 27/04/2010 
 */ {//1
//Connexion � la base
    conn_db();

// Affectation � une variable de l'ID de l'�quipe
    $id_equipe = $_SESSION['id_equipe'];
    if ($_SESSION['id_equipe'] == "admin") {
        die('<META HTTP-equiv="refresh" content=0;URL=admin.php>');
    }

// R�cup�ration du nom de l'�quipe
    $sql = 'SELECT * from equipes WHERE id_equipe = \'' . $id_equipe . '\' LIMIT 1';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

    if (mysql_num_rows($req) > 0) {    // si la requ�te comporte un r�sultat
        $data = mysql_fetch_assoc($req);
        $nom_equipe = $data['nom_equipe'];  // et on r�cup�re la valeur nom_equipe que l'on affecte � une variable
        $compet = $data['code_competition'];    // on r�cup�re aussi la valeur du code de competition
    }
//====================================================================
// on affiche le lien "se d�connecter" 
//====================================================================
    affich_connecte();

//====================================================================
// Titre de l'accueil
//====================================================================
    echo'<h1>' . $nom_equipe . ' - Vos matches </h1>';

    echo'<div id="liste_matches_equipe"></div>';
    echo'<script type="text/javascript" src="js/grilleListeMatchesEquipe.js"></script>';

////====================================================================
//// On regarde � quelles comp�titions l'�quipe est inscrite
////====================================================================
//    $sql = 'SELECT DISTINCT code_competition from matches WHERE id_equipe_dom = \'' . $id_equipe . '\' OR id_equipe_ext = \'' . $id_equipe . '\'';
//    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
//    while ($data = mysql_fetch_array($req)) {
////====================================================================
//// On affiche les matches de la comp�tition en question
////====================================================================
//        affich_matches_equipe($id_equipe, $data['code_competition']);
//    }
//====================================================================
// On affiche les d�tails de l'�quipe
//====================================================================
    echo'<div id="details_equipe">';
    echo'<a name="me"></a>';
    affich_details_equipe($id_equipe, $compet);
    echo'<p><div id="bouton_modif_equipe"></div></p>';
    echo'<script type="text/javascript" src="js/boutonModifEquipe.js"></script>';
//    echo'<p><span><A href="javascript:popup(\'change.php?a=me&i=' . $id_equipe . '&c=' . $compet . '\')">Modifier les informations</A></span></p>';
    echo'</div>';
    echo'</div>';
}

//************************************************************************************************
//************************************************************************************************
function affich_matches_equipe($id_equipe, $compet)
//************************************************************************************************
/*
 * * Fonction    : affich_matches_champ
 * * Input       : $id_equipe id de l'�quipe � modifier
 * * Output      : aucun 
 * * Description : Affiche les matches de championnat  d'une �quipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 06/05/2010 
 */ {
//Connexion � la base
    conn_db();

// On r�cup�re le libell� de la comp�tition
    $sql = 'SELECT libelle FROM competitions WHERE code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $libelle = $data['libelle'];
    }

//on affiche le calque correspondant et on construit la table
    echo'<div class="liste_matches"><h2>' . $libelle . '</h2>';
    echo'<table>';
    echo '   <tr>';
    echo '		<th>&nbsp;</td>';
    echo '		<th>Code match</td>';
    echo '		<th>Heure</td>';
    echo '		<th>Date</td>';
    echo '		<th colspan="5">Rencontres</td>';
    echo '		<th colspan="5">D�tails de sets</td>';
    echo '		<th>&nbsp;</td>';
    echo '	</tr>';

//====================================================================
// R�cup�ration de la liste des matchs de l'�quipe
//====================================================================
    $sql = 'SELECT * FROM matches WHERE (id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\') OR (id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\') ORDER BY journee ASC';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
//====================================================================
// Si on est en mode modification on affiche le d�tail du match
//====================================================================
        if (isset($_GET['i']) && !empty($_GET['i']) && $_GET['i'] == $data['code_match']) {

//====================================================================
// Si on n'est pas en mode Admin on d�sactive certains contr�les 
//====================================================================
            if ($_SESSION['id_equipe'] == "admin") {
                $disabled = '';
            } else {
                $disabled = 'readonly="TRUE"';
            }

            $division = $data['division'];
            $code_match = '<input value="' . $data['code_match'] . '" name="code_match" type="text" size="3" maxlength="5" ' . $disabled . '/>*';
            $heure_reception = '<input value="' . $data['heure_reception'] . '" name="heure_reception" type="text" size="3" maxlength="5" ' . $disabled . '/>*';
            $date_reception = '<input value="' . date_fr($data['date_reception']) . '" name="date_reception" type="text" size="8" maxlength="8" ' . $disabled . '/>*';
            $score_equipe_dom = '<input value="' . $data['score_equipe_dom'] . '" name="score_equipe_dom" type="text" size="1" maxlength="2"/>';
            $score_equipe_ext = '<input value="' . $data['score_equipe_ext'] . '" name="score_equipe_ext" type="text" size="1" maxlength="2"/>';
            $set_1_dom = '<input value="' . $data['set_1_dom'] . '" name="set_1_dom" type="text" size="1" maxlength="2"/>';
            $set_2_dom = '<input value="' . $data['set_2_dom'] . '" name="set_2_dom" type="text" size="1" maxlength="2"/>';
            $set_3_dom = '<input value="' . $data['set_3_dom'] . '" name="set_3_dom" type="text" size="1" maxlength="2"/>';
            $set_4_dom = '<input value="' . $data['set_4_dom'] . '" name="set_4_dom" type="text" size="1" maxlength="2"/>';
            $set_5_dom = '<input value="' . $data['set_5_dom'] . '" name="set_5_dom" type="text" size="1" maxlength="2"/>';
            $set_1_ext = '<input value="' . $data['set_1_ext'] . '" name="set_1_ext" type="text" size="1" maxlength="2"/>';
            $set_2_ext = '<input value="' . $data['set_2_ext'] . '" name="set_2_ext" type="text" size="1" maxlength="2"/>';
            $set_3_ext = '<input value="' . $data['set_3_ext'] . '" name="set_3_ext" type="text" size="1" maxlength="2"/>';
            $set_4_ext = '<input value="' . $data['set_4_ext'] . '" name="set_4_ext" type="text" size="1" maxlength="2"/>';
            $set_5_ext = '<input value="' . $data['set_5_ext'] . '" name="set_5_ext" type="text" size="1" maxlength="2"/>';
            $gagnea5_dom = '<input value="' . $data['gagnea5_dom'] . '" name="gagnea5_dom" type="checkbox" />*';
            $gagnea5_ext = '<input value="' . $data['gagnea5_ext'] . '" name="gagnea5_ext" type="checkbox" />*';
            $report = '<input value="1" name="report" type="checkbox" ' . $disabled . '/>*';
            $certif = '<input value="1" name="certif" type="checkbox" ' . $disabled . '/>*';

//====================================================================
// Affichage des valeurs
//====================================================================
            echo'   <tr>';
            echo'		<td colspan="15">';
            echo'		<table class="table_modif_score">';
            echo'<form id="post_score" action="includes/traitement.php?a=mr" method="post">';
            echo'		<tr><td colspan="15">&nbsp;</td></tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td class="modif_score_gras">Code Match</td><td>' . $code_match . '</td><td>&nbsp;</td>';
            echo'		  <td class="modif_score_gras">' . recup_nom_equipe($compet, $data['id_equipe_dom']) . '</td><td>' . $score_equipe_dom . '</td><td>/</td>';
            echo'		  <td>' . $score_equipe_ext . '</td><td class="modif_score_gras">' . recup_nom_equipe($compet, $data['id_equipe_ext']) . '</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr><td colspan="15">&nbsp;</td></tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td class="modif_score_gras">Heure</td><td class="data_modif_score">' . $heure_reception . '</td><td>&nbsp;</td>';
            echo'		  <td class="champ_modif_score">1er Set</td><td>' . $set_1_dom . '</td><td>-</td><td>' . $set_1_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td class="modif_score_gras">Date</td><td class="data_modif_score">' . $date_reception . '</td><td>&nbsp;</td>';
            echo'		  <td class="champ_modif_score">2i�me Set</td><td>' . $set_2_dom . '</td><td>-</td><td>' . $set_2_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td colspan="3"></td>';
            echo'		  <td class="champ_modif_score">3i�me Set</td><td>' . $set_3_dom . '</td><td>-</td><td>' . $set_3_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td class="modif_score_gras">Report�</td><td class="data_modif_score">' . $report . '</td><td>&nbsp;</td>';
            echo'		  <td class="champ_modif_score">4i�me Set</td><td>' . $set_4_dom . '</td><td>-</td><td>' . $set_4_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td class="modif_score_gras">Certifi�</td><td class="data_modif_score">' . $certif . '</td><td>&nbsp;</td>';
            echo'		  <td class="champ_modif_score">5i�me Set</td><td>' . $set_5_dom . '</td><td>-</td><td>' . $set_5_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr><td colspan="15">&nbsp;</td></tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td colspan="2">&nbsp;</td><td colspan="2" class="champ_modif_score">Match gagn� � 5</td><td class="champ_modif_score">' . $gagnea5_dom . '</td>';
            echo'		  <td>&nbsp;</td><td>' . $gagnea5_ext . '</td><td>&nbsp;</td><td><input value="Valider" name="valider" class="submit" id="valider" type="submit" /></td>';
            echo'		</tr>';
            echo'		<tr><td colspan="3">(*) R�serv� aux administrateurs</td><td colspan="12">&nbsp;</td></tr>';
            echo'<input value="' . $data['id_equipe_dom'] . '" name="id_equipe_dom" type="hidden" />';
            echo'<input value="' . $data['id_equipe_ext'] . '" name="id_equipe_ext" type="hidden" />';
            echo'<input value="' . $compet . '" name="compet" type="hidden" />';
            echo'<input value="' . $division . '" name="division" type="hidden" />';
            echo'</form>';
            echo'		</table>';
            echo'		</td>';
            echo'	</tr>';
        } else {
//====================================================================
// Sinon on affiche la ligne du match pour lecture
//====================================================================
            $code_match = $data['code_match'];
            $heure_reception = $data['heure_reception'];
            $date_reception = date_fr($data['date_reception']);
            $score_equipe_dom = $data['score_equipe_dom'];
            $score_equipe_ext = $data['score_equipe_ext'];
            $set_1_dom = $data['set_1_dom'];
            $set_2_dom = $data['set_2_dom'];
            $set_3_dom = $data['set_3_dom'];
            $set_4_dom = $data['set_4_dom'];
            $set_5_dom = $data['set_5_dom'];
            $set_1_ext = $data['set_1_ext'];
            $set_2_ext = $data['set_2_ext'];
            $set_3_ext = $data['set_3_ext'];
            $set_4_ext = $data['set_4_ext'];
            $set_5_ext = $data['set_5_ext'];
            $gagnea5_dom = $data['gagnea5_dom'];
            $gagnea5_ext = $data['gagnea5_ext'];
            $report = $data['report'];
            $certif = $data['certif'];

//====================================================================
// Traitement des cellules vides si nulles
//====================================================================
            if ($score_equipe_dom == 0 && $score_equipe_ext == 0) {
                $score_equipe_dom = "&nbsp;";
                $score_equipe_ext = "&nbsp;";
            }

            if ($set_1_dom == 0 && $set_1_ext == 0) {
                $set1 = "&nbsp;";
            } else {
                $set1 = $set_1_dom . '/' . $set_1_ext;
            }

            if ($set_2_dom == 0 && $set_2_ext == 0) {
                $set2 = "&nbsp;";
            } else {
                $set2 = $set_2_dom . '/' . $set_2_ext;
            }

            if ($set_3_dom == 0 && $set_3_ext == 0) {
                $set3 = "&nbsp;";
            } else {
                $set3 = $set_3_dom . '/' . $set_3_ext;
            }

            if ($set_4_dom == 0 && $set_4_ext == 0) {
                $set4 = "&nbsp;";
            } else {
                $set4 = $set_4_dom . '/' . $set_4_ext;
            }

            if ($set_5_dom == 0 && $set_5_ext == 0) {
                $set5 = "&nbsp;";
            } else {
                $set5 = $set_5_dom . '/' . $set_5_ext;
            }

//====================================================================
//Traitement de l'affichage des matches gagn�s
//====================================================================
            $class_dom = "equipes_dom";
            $class_ext = "equipes_ext";
            if ($score_equipe_dom > $score_equipe_ext) {
                $class_dom = "equipes_dom_gagne";
            }
            if ($score_equipe_dom < $score_equipe_ext) {
                $class_ext = "equipes_ext_gagne";
            }

//====================================================================
//Traitement de l'affichage des matches report�s
//====================================================================

            $class_report = "date";
            if ($report == 1) {
                $class_report = "date_report";
            }

//====================================================================
// Traitement des feuilles de matches certifi�es
//====================================================================
            if ($certif == '1') {
                $certif = '<img src="images/certif.gif" title="Feuille de match re�ue et certifi�e" />';
            } else {
                $certif = '<a href="portail.php?a=ms&i=' . $code_match . '" target="_self"><img src="images/modif.gif" width="17" height="19" title="Modifier le score" /></a>';
            }

//====================================================================
// Traitement des liens http vers feuille de match des �quipes
//====================================================================
            $lien_equipe_dom = '<a href="get.php?id=' . $data['id_equipe_dom'] . '" target="_blank">' . recup_nom_equipe($compet, $data['id_equipe_dom']) . '</a>';
            $lien_equipe_ext = '<a href="get.php?id=' . $data['id_equipe_ext'] . '" target="_blank">' . recup_nom_equipe($compet, $data['id_equipe_ext']) . '</a>';

//====================================================================
// Affichage des valeurs
//====================================================================
            echo'   <tr>';
            echo'		<td>&nbsp;</td>';
            echo'		<td class="code_match">' . $code_match . '</td>';
            echo'		<td class="heure">' . $heure_reception . '</td>';
            echo'		<td class="' . $class_report . '">' . $date_reception . '</td>';
            echo'		<td class="' . $class_dom . '">' . $lien_equipe_dom . '</td>';
            echo'		<td class="score">' . $score_equipe_dom . '</td>';
            echo'		<td class="score">/</td>';
            echo'		<td class="score">' . $score_equipe_ext . '</td>';
            echo'		<td class="' . $class_ext . '">' . $lien_equipe_ext . '</td>';
            echo'		<td class="sets">' . $set1 . '</td>';
            echo'		<td class="sets">' . $set2 . '</td>';
            echo'		<td class="sets">' . $set3 . '</td>';
            echo'		<td class="sets">' . $set4 . '</td>';
            echo'		<td class="sets">' . $set5 . '</td>';
            echo'		<td class="certif">' . $certif . '</td>';
            echo'	</tr>';
        }
    }

    echo'</table>';
    echo'</div>';
}

//************************************************************************************************
//************************************************************************************************
function modif_equipe($id_equipe, $compet)
//************************************************************************************************
/*
 * * Fonction    : modif_equipe
 * * Input       : $id_equipe id de l'�quipe � modifier
 * * Output      : aucun 
 * * Description : Modifie les informations d'une �quipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 04/05/2010 
 */ {
//Connexion � la base
    conn_db();

// Affectation � une variable de l'ID de l'�quipe
    $id_equipe = $_SESSION['id_equipe'];
    $nom_equipe = recup_nom_equipe($id_equipe, $compet);

// On cr�e le d�but du formulaire
    echo'  <form id="modif_equipe" action="includes/traitement.php?a=me" method="post">';
    echo'    <h1>Modification de l\'�quipe ' . $nom_equipe . '</h1>';
    echo'    <table>';

// on ex�cute la requ�te
    $sql = 'SELECT * FROM `details_equipes`  WHERE `id_equipe` = \'' . $id_equipe . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
//on affiche les donn�es
        echo'      <tr>';
        echo'		<td>Responsable :</td>';
        echo'		<td><input value="' . $data['responsable'] . '" name="responsable" type="text" size="50" maxlength="50" /></td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>T�l�phone 1 :</td>';
        echo'		<td><input value="' . $data['telephone_1'] . '" name="telephone_1" type="text" size="50" maxlength="14" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>T�l�phone 2 :</td>';
        echo'		<td><input value="' . $data['telephone_2'] . '" name="telephone_2" type="text" size="50" maxlength="14" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Email :</td>';
        echo'		<td><input value="' . $data['email'] . '" name="email" type="text" size="50" maxlength="50" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>R�ception le :</td>';
        echo'		<td><input value="' . $data['jour_reception'] . '" name="jour_reception" type="text" size="50" maxlength="10" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Horaire :</td>';
        echo'		<td><input value="' . $data['heure_reception'] . '" name="heure_reception" type="text" size="50" maxlength="5" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Gymnase :</td>';
        echo'		<td><input value="' . $data['gymnase'] . '" name="gymnase" type="text" size="50" maxlength="200" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Localisation GPS :</td>';
        echo'		<td><input value="' . $data['localisation'] . '" name="localisation" type="text" size="50" maxlength="200" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Site Web :</td>';
        echo'		<td><input value="' . $data['site_web'] . '" name="site_web" type="text" size="50" maxlength="50" /><td>';
        echo'	  </tr>';
        echo'<input value="' . $id_equipe . '" name="id_equipe" type="hidden" />';
    }

    echo'    </table>';
    echo'<p><span><input value="Valider" name="valider" class="submit" id="valider" type="submit" /></span></p>';
    echo'  </form>';
}

//************************************************************************************************
//************************************************************************************************
function calcul_classement($id_equipe, $compet, $division)
//************************************************************************************************
/*
 * * Fonction    : calcul_classement 
 * * Input       : STRING $id_equipe,$compet, $div
 * * Output      : aucun 
 * * Description : calcule les points de l'�quipe qui dont le score vient d'�tre modifi�
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 06/05/2010 
 */ {//1
//Connexion � la base
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
    $match_gagnes = $match_gag_dom + $match_gag_ext;   // Matches gagn�s 
    $match_perdus = $match_per_dom + $match_per_ext;  // Matches perdus
    $match_joues = $match_gagnes + $match_perdus;   // Matches jou�s
    $sets_marques = $sets_mar_dom + $sets_mar_ext;   // Sets marqu�s
    $sets_encaisses = $sets_enc_dom + $sets_enc_ext;  // Sets encaiss�s
    $difference = $sets_marques - $sets_encaisses;   // Diff�rence de sets			
    $gagnea5 = $gagnea5_dom + $gagnea5_ext;     // Matches gagn�s � 5 joueurs
    $forfait = $forfait_dom + $forfait_ext;     // Matches perdus par forfait

    $points = 3 * $match_gagnes + $match_perdus - $forfait - $gagnea5 - $penalite;
    $pts_marques = $pts_mar_dom + $pts_mar_ext;
    $pts_encaisses = $pts_enc_dom + $pts_enc_ext;

    $points = 3 * $match_gagnes + $match_perdus - $forfait - $gagnea5 - $penalite;
    $pts_marques = $pts_mar_dom + $pts_mar_ext;
    $pts_encaisses = $pts_enc_dom + $pts_enc_ext;



// On �vite la division par 0 pour le calcul des points
    if ($pts_encaisses != 0) {
        $coeff_points = ($pts_marques / $pts_encaisses);
    } else {
        $coeff_points = $pts_marques;
    }
// On �vite la division par 0 pour le calcul des sets
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
        $results[] = $data;
        $rang++;
    }
    return json_encode($results);
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
//addSqlActivity($sqlmaj);
    calcul_classement($id_equipe, $compet, $division);
    mysql_close();
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
//addSqlActivity($sqlmaj);
    calcul_classement($id_equipe, $compet, $division);
    mysql_close();
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
    return true;
}

function certifierMatch($code_match) {
    conn_db();
    $sql = 'UPDATE matches SET certif = 1 WHERE code_match = \'' . $code_match . '\'';
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
//addSqlActivity($sql);
    mysql_close();
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
//addSqlActivity($sql);
    calcul_classement($id_equipe_dom, $compet, $division);
    calcul_classement($id_equipe_ext, $compet, $division);
    mysql_close();
    return true;
}

function addSqlActivity($sql) {
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "INSERT activity SET comment=\"$sql\", activity_date=CURDATE(), user_id=$sessionIdEquipe";
    $req = mysql_query($sql);
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
//addSqlActivity($sql);
    $sql = "UPDATE equipes SET "
            . "id_club=$id_club "
            . "WHERE id_equipe=$id_equipe";
    $req = mysql_query($sql);
    if ($req === FALSE) {
        return false;
    }
//addSqlActivity($sql);
    mysql_close();
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
//addSqlActivity($sql);
    mysql_close();
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
//addSqlActivity($sql);
    mysql_close();
    return true;
}

function getMatches($compet, $div) {
    conn_db();
    $sql = "SELECT 
        m.id_match,
        m.code_match,
        m.code_competition,
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
        m.gagnea5_dom+0 AS gagnea5_dom,
        m.gagnea5_ext+0 AS gagnea5_ext,
        m.forfait_dom+0 AS forfait_dom,
        m.forfait_ext+0 AS forfait_ext,
        m.certif+0 AS certif,
        m.report+0 AS report,
        m.retard+0 AS retard
        FROM matches m 
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        JOIN journees j ON j.numero=m.journee AND j.code_competition=m.code_competition
        WHERE m.code_competition = '$compet' AND m.division = '$div' ORDER BY code_match";
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

function getMesMatches() {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT 
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
        JOIN journees j ON j.numero=m.journee AND j.code_competition=m.code_competition
        WHERE m.id_equipe_dom = $sessionIdEquipe OR m.id_equipe_ext = $sessionIdEquipe
        ORDER BY m.date_reception, m.code_match";
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
        e.id_club,
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
        WHERE d.id_equipe = $sessionIdEquipe";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    return json_encode($results);
}

function getMyPlayers() {
    conn_db();
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    if ($_SESSION['id_equipe'] == "admin") {
        return false;
    }
    $sessionIdEquipe = $_SESSION['id_equipe'];
    $sql = "SELECT 
        j.prenom, 
        j.nom, 
        j.telephone, 
        j.email, 
        j.num_licence, 
        CONCAT('images/joueurs/', UPPER(j.nom), LOWER(j.prenom), '.jpg') AS path_photo,
        j.sexe, 
        j.departement_affiliation, 
        j.est_actif+0 AS est_actif, 
        j.id_club, 
        j.adresse, 
        j.code_postal, 
        j.ville, 
        j.telephone2, 
        j.email2, 
        j.telephone3, 
        j.telephone4, 
        j.est_licence_valide+0 AS est_licence_valide, 
        j.est_responsable_club+0 AS est_responsable_club, 
        j.id, 
        j.date_homologation
        FROM joueur_equipe je
        LEFT JOIN joueurs j ON j.id=je.id_joueur
        WHERE je.id_equipe = $sessionIdEquipe";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $index => $result) {
        if (file_exists("../" . $result['path_photo']) === FALSE) {
            switch ($result['sexe']) {
                case 'M':
                    $results[$index]['path_photo'] = 'images/joueurs/Male.png';
                    break;
                case 'F':
                    $results[$index]['path_photo'] = 'images/joueurs/Female.jpg';
                    break;
                default:
                    break;
            }
        }
    }
    return json_encode($results);
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
        CONCAT('images/joueurs/', UPPER(j.nom), LOWER(j.prenom), '.jpg') AS path_photo,
        j.sexe, 
        j.departement_affiliation, 
        j.est_actif+0 AS est_actif, 
        j.id_club, 
        j.adresse, 
        j.code_postal, 
        j.ville, 
        j.telephone2, 
        j.email2, 
        j.telephone3, 
        j.telephone4, 
        j.est_licence_valide+0 AS est_licence_valide, 
        j.est_responsable_club+0 AS est_responsable_club, 
        j.id, 
        j.date_homologation
        FROM joueurs j";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    foreach ($results as $index => $result) {
        if (file_exists("../" . $result['path_photo']) === FALSE) {
            switch ($result['sexe']) {
                case 'M':
                    $results[$index]['path_photo'] = 'images/joueurs/Male.png';
                    break;
                case 'F':
                    $results[$index]['path_photo'] = 'images/joueurs/Female.jpg';
                    break;
                default:
                    break;
            }
        }
    }
    return json_encode($results);
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
    if ($req === FALSE) {
        return false;
    }
    //addSqlActivity($sql);
    mysql_close();
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
    //addSqlActivity($sql);
    mysql_close();
    return true;
}
