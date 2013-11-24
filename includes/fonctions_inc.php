<?php

session_start();

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
//Déclaration des constantes
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

// on se connecte à MySQL 
    $db = mysql_connect($server, $user, $password);
    mysql_select_db($base, $db);
}

//************************************************************************************************
//************************************************************************************************
function affich_classement($compet, $div)
//************************************************************************************************
/*
 * * Fonction    : affich_classement 
 * * Input       : STRING $div, STRING $compet
 * * Output      : aucun 
 * * Description : affichage du classement de la division $div, de la competition $compet
 * * Creator     : Jean-Marc BERNARD 
 * * Date        : 07/04/2010 
 */ {
    $n = 1;

//Connexion à la base
    conn_db();

// ***** SPECIFIQUE ADMINISTRATION ***********************************************
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        echo'<form id="liste_equipe" action="includes/traitement.php?a=ie" method="post">';
        echo'<ul><li>';
        echo'<a href="?a=ie&c=' . $compet . '&d=' . $div . '" target="_self" class="lien">Inscrire une équipe</a></li>';
        if (isset($_GET['a']) && $_GET['a'] == 'ie') {
            echo'<li>';
            echo'<SELECT name="id_equipe" onchange="submit();">';
            echo'<OPTION value="">Choisir une équipe</OPTION>';

            $sql = 'SELECT * FROM equipes WHERE code_competition = \'' . recup_compet_maitre($compet) . '\' AND id_equipe NOT IN (SELECT id_equipe FROM classements where code_competition = \'' . $compet . '\') ORDER BY nom_equipe';
            $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
            while ($data = mysql_fetch_assoc($req)) {
                echo'<OPTION value="' . $data['id_equipe'] . '">' . $data['nom_equipe'] . '</OPTION>';
            }
            echo'</SELECT>';
            echo'<input name="compet" value="' . $compet . '" type="hidden">';
            echo'<input name="div" value="' . $div . '" type="hidden">';
            echo'</li>';
        }
        echo'</ul>';
        echo '</form>';
    }
// ***** FIN SPECIFIQUE ADMINISTRATION *******************************************
//Requête SQL
    $sql = 'SELECT * FROM classements WHERE code_competition = \'' . $compet . '\' AND division = \'' . $div . '\' ORDER BY points DESC, difference DESC, coeff_points DESC';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

//Affichage des résultats
    echo '<table>';
    echo'<tr>';
    echo'<th>Rang</th>';
    echo'<th class="equipes">Equipes</th>';
    echo'<th>Pts</th>';
    echo'<th>Jou.</th>';
    echo'<th>Gag.</th>';
    echo'<th>Per.</th>';
    echo'<th>Sets P.</th>';
    echo'<th>Sets C.</th>';
    echo'<th>Diff.</th>';
    echo'<th>Coeff S.</th>';
    echo'<th>Pts P.</th>';
    echo'<th>Pts C.</th>';
    echo'<th>Coeff P.</th>';
    echo'<th>Pnlts</th>';

// ***** SPECIFIQUE ADMINISTRATION ***********************************************
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        echo'<th>Administration</th>';
    }
// ***** FIN SPECIFIQUE ADMINISTRATION *******************************************

    echo'</tr>';
    while ($data = mysql_fetch_assoc($req)) {

// On affiche le résultat dans le tableau
        echo'<tr>';
        echo'<td>' . $n . '.</td>';
        echo'<td class="equipes">' . recup_nom_equipe($compet, $data['id_equipe']) . '</td>';
        echo'<td class="points">' . $data['points'] . '</td>';
        echo'<td>' . $data['joues'] . '</td>';
        echo'<td>' . $data['gagnes'] . '</td>';
        echo'<td>' . $data['perdus'] . '</td>';
        echo'<td>' . $data['sets_pour'] . '</td>';
        echo'<td>' . $data['sets_contre'] . '</td>';
        if ($data['difference'] > 0) {
            $diff = '+' . $data['difference'];
        } else {
            $diff = $data['difference'];
        }
        echo'<td>' . $diff . '</td>';
        echo'<td>' . $data['coeff_sets'] . '</td>';
        echo'<td>' . $data['points_pour'] . '</td>';
        echo'<td>' . $data['points_contre'] . '</td>';
        echo'<td>' . $data['coeff_points'] . '</td>';
        echo'<td>' . $data['penalite'] . '</td>';

// ***** SPECIFIQUE ADMINISTRATION ***********************************************
        if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
            echo'<td>';
            echo'<span class="pen_equipe"><a href="includes/traitement.php?a=gpa&&i=' . $data['id_equipe'] . '&c=' . $compet . '" onclick="return confirm(\'Voulez-vous ajouter un point de pénalité à cette équipe ?\');"><img src="images/moins.png" title="Ajouter un point de pénalité" /></a>';
            echo'<a href="includes/traitement.php?a=gpe&i=' . $data['id_equipe'] . '&c=' . $compet . '" onclick="return confirm(\'Voulez-vous annuler un point de pénalité pour cette équipe ?\');"><img src="images/plus.png" title="Enlever un point de pénalité" /></a><a href="includes/traitement.php?a=sec&i=' . $data['id_equipe'] . '&c=' . $compet . '" onclick="return confirm(\'Cette opération entrainera la suppression de cette équipe de cette compétition ! Êtes-vous sur ?\');"><img src="images/delete.gif" title="Supprimer cette équipe de la compétition" /></a></span>';
        }
        echo'</td>';
// ***** FIN SPECIFIQUE ADMINISTRATION *******************************************


        echo'</tr>';
        $n = $n + 1;
    }
    echo'</table>';

// Fermeture de la connexion à mysql 
//mysql_close(); 
}

//************************************************************************************************
//************************************************************************************************
function affich_journee($compet, $div)
//************************************************************************************************
/*
 * * Fonction    : affich_journee 
 * * Input       : STRING $div = division concernée, $compet = journée concernée 
 * * Output      : aucun 
 * * Description : affichage de la division et de la journee passee en variable
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 07/05/2010 
 */ {
//Connexion à la base
    conn_db();


//********************************************************************************
// ***** SPECIFIQUE ADMINISTRATION ***********************************************
//********************************************************************************
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        echo '<ul><li><a href="?a=am&c=' . $compet . '&d=' . $div . '" target="_self" class="lien">Ajouter un match</a></li></ul>';
        if (isset($_GET['a']) && $_GET['a'] == "am") {
            echo'<form name="ajout_match" action="includes/traitement.php?a=am" method="post">';
            echo'<table class="admin"><tr class="admin"><td class="w80">Code Match</td><td class="w80">Journée</td><td class="w80">Heure</td><td class="w80">Date</td><td class="w150">Equipe 1</td><td class="w150">Equipe 2</td></tr>';
            echo'<tr><td class="w80"><input value="" name="code_match" type="text" size="4" maxlength="5" /></td>';

//----------------------------------------------------------------------------------------------------------
// on récupère le nombre de journée créées pour cette compétition 
//----------------------------------------------------------------------------------------------------------
            echo'<td class="w80"><select name="journee"><option></option>';
            $sql = 'SELECT DISTINCT COUNT(numero) FROM journees WHERE code_competition = \'' . $compet . '\' AND division = \'' . $div . '\'';
            $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
            $data = mysql_fetch_assoc($req);
            $nb_journee = $data['COUNT(numero)'];
            if ($nb_journee == 0) {
                $sql = 'SELECT DISTINCT COUNT(numero) FROM journees WHERE code_competition = \'' . $compet . '\' AND division = \'1\'';
                $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
                $data = mysql_fetch_assoc($req);
                $nb_journee = $data['COUNT(numero)'];
            }
            for ($i = 1; $i < $nb_journee + 1; $i++) {    //tant que i est inférieur au nombre de journée on affiche les matches
                echo'<option value="' . $i . '">' . $i . '</option>';
            }
            echo'</select></td>';
            echo'<td class="w80"><input value="hhHmm" name="heure_reception" type="text" size="5" maxlength="5" /></td>';
            echo'<td class="w80"><input value="jj/mm/aaaa" name="date_reception" type="text" size="8" maxlength="10" /></td>';

//----------------------------------------------------------------------------------------------------------
// on récupère la liste des équipes inscrites à la compétition 
//----------------------------------------------------------------------------------------------------------
            echo'<td class="w150"><select name="id_equipe_dom"><option>Choisir une équipe</option>';
            $sql = 'SELECT id_equipe FROM classements WHERE code_competition = \'' . $compet . '\' AND division = \'' . $div . '\'';
            $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
            while ($data = mysql_fetch_assoc($req)) {
                echo'<option value="' . $data['id_equipe'] . '">' . recup_nom_equipe($compet, $data['id_equipe']) . '</option>';
            }
            echo'</select></td>';

//----------------------------------------------------------------------------------------------------------
// on récupère la liste des équipes inscrites à la compétition
//----------------------------------------------------------------------------------------------------------
            echo'<td class="w150"><select name="id_equipe_ext"><option>Choisir une équipe</option>';
            $sql = 'SELECT id_equipe FROM classements WHERE code_competition = \'' . $compet . '\' AND division = \'' . $div . '\'';
            $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
            while ($data = mysql_fetch_assoc($req)) {
                echo'<option value="' . $data['id_equipe'] . '">' . recup_nom_equipe($compet, $data['id_equipe']) . '</option>';
            }
            echo'</select></td>';
//----------------------------------------------------------------------------------------------------------

            echo'</tr></table>';
            echo'<input type="hidden" name="code_competition" value="' . $compet . '" />';
            echo'<input type="hidden" name="division" value="' . $div . '" />';
            echo'<input type="submit" value="Valider" name="submit" class="submit" />';
            echo'</form>';
        }
    }
//********************************************************************************
// ***** FIN SPECIFIQUE ADMINISTRATION *******************************************
//********************************************************************************
// On regarde le nombre de journée présente pour cette compétition 
    $sql = 'SELECT COUNT( DISTINCT journee ) FROM `matches` WHERE code_competition = \'' . $compet . '\' AND division = \'' . $div . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $data = mysql_fetch_assoc($req);
    $nbr_journee = $data['COUNT( DISTINCT journee )'];
    for ($i = 1; $i < $nbr_journee + 1; $i++) {    //tant que i est inférieur au nombre de journée on affiche les matches
// on récupère le nommage et la valeur de la journée
        $sql = 'SELECT nommage, libelle FROM journees WHERE code_competition = \'' . $compet . '\' AND division = \'' . $div . '\' AND numero = \'' . $i . '\'';
        $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
        $data = mysql_fetch_assoc($req);
        $nommage_journee = $data['nommage'];
        $libelle_journee = $data['libelle'];

// si aucune donnée est récupérée on affiche les valeurs de la division 1
        if ($nommage_journee == "" && $libelle_journee == "") {
            $sql = 'SELECT nommage, libelle FROM journees WHERE code_competition = \'' . $compet . '\' AND division = 1 AND numero = \'' . $i . '\'';
            $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
            $data = mysql_fetch_assoc($req);
            $nommage_journee = $data['nommage'];
            $libelle_journee = $data['libelle'];
        }

// on affiche l'intitulé de la journée
        echo '<H1>' . $nommage_journee . ' - ' . $libelle_journee . '</H1>';

// On créé la table de la journée sélectionnée
        echo '<table>';

// on récupère les matches de la journée
        $sql = 'SELECT * FROM matches WHERE code_competition = \'' . $compet . '\' AND division = \'' . $div . '\' AND journee = \'' . $i . '\' ORDER BY code_match';
        $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
        while ($data = mysql_fetch_assoc($req)) {

// Mise en variable des datas récoltées
            $match = $data['code_match'];
            $horaire = $data['heure_reception'];
            $date = date_fr($data['date_reception']);
            $score1 = $data['score_equipe_dom'];
            $score2 = $data['score_equipe_ext'];
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
            $nb_retard = $data['retard'];

//Traitement des matches en retard
            $oriDateTime = DateTime::createFromFormat('Y-m-d', $data['date_reception']);
            $nowDateTime = new DateTime();
            $interval = $nowDateTime->diff($oriDateTime);
            $diff_jour = intval($interval->format('%r%a'));
// On vérifie la différence de date
            if ($diff_jour > -16 && $diff_jour < -10 && $score1 == 0 && $score2 == 0) {
                $retard = '<img src="images/warn1.gif" title="! ATTENTION ! Match en retard ou non renseigné de plus de 10 jours !" />';
                // On regarde si le message a déjà été envoyé
                if ($nb_retard == 0) {
                    envoi_mail($data['id_equipe_dom'], $data['id_equipe_ext'], $compet, $date, 1);
                    // Mise à jour de la base
                    $sqlmaj = 'UPDATE matches SET retard = 1 WHERE code_match = \'' . $match . '\'';
                    $reqmaj = mysql_query($sqlmaj) or die('Erreur SQL !<br>' . $sqlmaj . '<br>' . mysql_error());
                }
            } elseif ($diff_jour < -15 && $score1 == 0 && $score2 == 0) {
                $retard = '<img src="images/warn2.gif" title="! ATTENTION ! Match en retard ou non renseigné de plus de 15 jours !" />';
                // On regarde si le message a déjà été envoyé
                if ($nb_retard == 1) {
                    envoi_mail($data['id_equipe_dom'], $data['id_equipe_ext'], $compet, $date, 2);
                    // Mise à jour de la base
                    $sqlmaj = 'UPDATE matches SET retard = 2 WHERE code_match = \'' . $match . '\'';
                    $reqmaj = mysql_query($sqlmaj) or die('Erreur SQL !<br>' . $sqlmaj . '<br>' . mysql_error());
                }
            } else {
                $retard = "&nbsp;";
            }



// Traitement des cellules vides si nulles
            if ($score1 == 0 && $score2 == 0) {
                $score1 = "&nbsp;";
                $score2 = "&nbsp;";
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

//Traitement de l'affichage des matches gagnés
            $class_dom = "equipes_dom";
            $class_ext = "equipes_ext";
            if ($score1 > $score2) {
                $class_dom = "equipes_dom_gagne";
            }
            if ($score1 < $score2) {
                $class_ext = "equipes_ext_gagne";
            }

//Traitement de l'affichage des matches reportés
            $class_report = "date";
            if ($report == 1) {
                $class_report = "date_report";
            }

// On regarde si on est en administrateur et si un match est sélectionné en modification
            if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin" && isset($_GET['m']) && $_GET['m'] == $match) {
                echo'<tr><td colspan="14">';
                echo'<table class="admin">';
                echo'  <form name="modif_match" action="includes/traitement.php?a=mr" method="post">';
                echo'   <tr>';
                echo'		<td>' . $retard . '</td>';
                echo'		<td class="code_match">' . $match . '</td>';
                echo'		<td class="heure"><input value="' . $horaire . '" name="heure_reception" type="text" size="5" maxlength="5" /></td>';
                echo'		<td class="' . $class_report . '"><input value="' . $date . '" name="date_reception" type="text" size="8" maxlength="10" /></td>';
                echo'		<td class="' . $class_dom . '">' . recup_nom_equipe($compet, $data['id_equipe_dom']) . '</td>';
                echo'		<td class="score"><input value="' . $score1 . '" name="score_equipe_dom" type="text" size="1" maxlength="1" /></td>';
                echo'		<td class="score">/</td>';
                echo'		<td class="score"><input value="' . $score2 . '" name="score_equipe_ext" type="text" size="1" maxlength="1" /></td>';
                echo'		<td class="' . $class_ext . '">' . recup_nom_equipe($compet, $data['id_equipe_ext']) . '</td>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_1_dom . '" name="set_1_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_1_ext . '" name="set_1_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td colspan="6">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_2_dom . '" name="set_2_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_2_ext . '" name="set_2_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td colspan="6">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_3_dom . '" name="set_3_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_3_ext . '" name="set_3_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td colspan="6">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_4_dom . '" name="set_4_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_4_ext . '" name="set_4_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td colspan="6">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_5_dom . '" name="set_5_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_5_ext . '" name="set_5_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td>&nbsp;</td>';
                echo'		<td colspan="5"><input value="Modifier" name="submit" class="submit" type="submit" /></td>';
                echo'	  </tr>';
                echo'	<input value="' . $data['certif'] . '" name="certif" type="hidden" />';
                echo'	<input value="' . $match . '" name="code_match" type="hidden" />';
                echo'	<input value="' . $compet . '" name="compet" type="hidden" />';
                echo'	<input value="' . $div . '" name="division" type="hidden" />';
                echo'	<input value="' . $data['id_equipe_dom'] . '" name="id_equipe_dom" type="hidden" />';
                echo'	<input value="' . $data['id_equipe_ext'] . '" name="id_equipe_ext" type="hidden" />';
                echo'	<input value="' . $date . '" name="date_originale" type="hidden" />';
                echo'  </form>';
                echo'</table>';
                echo'</td></tr>';
            } else {
// Affichage des valeurs
                echo '   <tr>';
                echo '		<td>' . $retard . '</td>';
                echo '		<td class="code_match">' . $match . '</td>';
                echo '		<td class="heure">' . $horaire . '</td>';
                echo '		<td class="' . $class_report . '">' . $date . '</td>';
                echo '		<td class="' . $class_dom . '">' . recup_nom_equipe($compet, $data['id_equipe_dom']) . '</td>';
                echo '		<td class="score">' . $score1 . '</td>';
                echo '		<td class="score">/</td>';
                echo '		<td class="score">' . $score2 . '</td>';
                echo '		<td class="' . $class_ext . '">' . recup_nom_equipe($compet, $data['id_equipe_ext']) . '</td>';
                echo '		<td class="sets">' . $set1 . '</td>';
                echo '		<td class="sets">' . $set2 . '</td>';
                echo '		<td class="sets">' . $set3 . '</td>';
                echo '		<td class="sets">' . $set4 . '</td>';
                echo '		<td class="sets">' . $set5 . '</td>';

// ***** SPECIFIQUE ADMINISTRATION ***********************************************
                if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {

//Traitement de l'affichage des matches certifiés
                    $certif = $data['certif'];
                    if ($certif == 1) {
                        $certif = "";
                    } else {
                        $certif = '<a href="includes/traitement.php?a=cm&m=' . $match . '"><img src="images/certified.png" title="Certifier avoir reçu la feuille de ce match"  onclick="return confirm(\'Certifier le match ' . $match . ' ?\');" /></a>';
                    }

                    echo '	<td class="admin">' . $certif . '<a href="?a=mr&d=' . $div . '&m=' . $match . '"><img src="images/modif.gif" title="Modifier le score du match" /></a><a href="includes/traitement.php?a=sm&m=' . $match . '" onclick="return confirm(\'Cette opération entrainera irrémédiablement la suppression de ce match ! Êtes-vous sur de vouloir continuer ?\');"><img src="images/delete.gif" title="Supprimer ce match" /></a></td>';
                }
// ***** FIN SPECIFIQUE ADMINISTRATION *******************************************

                echo '	</tr>';
            }
        }
// on ferme la table de la journée sélectionnée
        echo '</table>';
    }

// Fermeture de la connexion à mysql 
//mysql_close(); 
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
 * * Description : Récupère le nom de la compétition
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 07/04/2011
 */ {
//Connexion à la base
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
function affich_pf_coupe($compet)
//************************************************************************************************
/*
 * * Fonction    : affich_pf_coupe 
 * * Input       : compet 
 * * Output      : aucun 
 * * Description : affichage des phases finale de la coupe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 20/04/2010 
 */ {
//Connexion à la base
    conn_db();

// ***** SPECIFIQUE ADMINISTRATION ***********************************************
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        echo '<ul><li><a href="?a=am&c=' . $compet . '" target="_self" class="lien">Ajouter un match</a></li></ul>';
        if (isset($_GET['a']) && $_GET['a'] == "am") {
            echo'<form name="ajout_match" action="includes/traitement.php?a=am" method="post">';
            echo'<table class="admin"><tr class="admin"><td class="w80">Code Match</td><td class="w80">Niveau</td><td class="w80">Heure</td><td class="w80">Date</td><td class="w150">Equipe 1</td><td class="w150">Equipe 2</td></tr>';
            echo'<tr><td class="w80"><input value="" name="code_match" type="text" size="4" maxlength="5" /></td>';

// on récupère le nombre de journée créées pour cette compétition 
            echo'<td class="w80"><select name="journee"><option></option>';
//----------------------------------------------------------------------------------------------------------
            $sql = 'SELECT numero,nommage FROM journees WHERE code_competition = \'' . $compet . '\'';
            $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
            while ($data = mysql_fetch_assoc($req)) {
                echo'<option value="' . $data['numero'] . '">' . $data['nommage'] . '</option>';
            }
            echo'</select></td>';
            echo'<td class="w80"><input value="hhHmm" name="heure_reception" type="text" size="5" maxlength="5" /></td>';
            echo'<td class="w80"><input value="jj/mm/aaaa" name="date_reception" type="text" size="8" maxlength="10" /></td>';

//----------------------------------------------------------------------------------------------------------
// on récupère la liste des équipes inscrites à la compétition 
//----------------------------------------------------------------------------------------------------------
            echo'<td class="w150"><select name="id_equipe_dom"><option>Choisir une équipe</option>';
            $sql = 'SELECT id_equipe FROM equipes WHERE code_competition = \'' . recup_compet_maitre($compet) . '\' ORDER BY nom_equipe';
            $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
            while ($data = mysql_fetch_assoc($req)) {
                echo'<option value="' . $data['id_equipe'] . '">' . recup_nom_equipe($compet, $data['id_equipe']) . '</option>';
            }
            echo'</select></td>';

//----------------------------------------------------------------------------------------------------------
// on récupère la liste des équipes inscrites à la compétition
//----------------------------------------------------------------------------------------------------------
            echo'<td class="w150"><select name="id_equipe_ext"><option>Choisir une équipe</option>';
            $sql = 'SELECT id_equipe FROM equipes WHERE code_competition = \'' . recup_compet_maitre($compet) . '\' ORDER BY nom_equipe';
            $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
            while ($data = mysql_fetch_assoc($req)) {
                echo'<option value="' . $data['id_equipe'] . '">' . recup_nom_equipe($compet, $data['id_equipe']) . '</option>';
            }
            echo'</select></td>';
//----------------------------------------------------------------------------------------------------------

            echo'</tr></table>';
            echo'<input type="hidden" name="code_competition" value="' . $compet . '" />';
            echo'<input type="hidden" name="division" value="1" />';
            echo'<input type="submit" value="Valider" name="submit" class="submit" />';
            echo'</form>';
        }
    }
// ***** FIN SPECIFIQUE ADMINISTRATION *******************************************
// On regarde le nombre de journée présente pour cette compétition 
    $sql = 'SELECT DISTINCT journee FROM `matches` WHERE code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

// pour tous les enregistrements trouvés on récupère le libellé et on affiche les matches
    while ($data = mysql_fetch_assoc($req)) {            //BOUCLE NOMBRE JOURNEE
        $journee = $data['journee'];
// on récupère le nommage et la valeur de la journée
        $sql2 = 'SELECT nommage, libelle FROM journees WHERE code_competition = \'' . $compet . '\' AND numero = \'' . $journee . '\'';
        $req2 = mysql_query($sql2) or die('Erreur SQL !<br>' . $sql2 . '<br>' . mysql_error());
        $data2 = mysql_fetch_assoc($req2);
        $nommage_journee = $data2['nommage'];
        $libelle_journee = $data2['libelle'];

// on affiche l'intitulé de la journée
        echo '<H1>' . $nommage_journee . ' - ' . $libelle_journee . '</H1>';

// On créé la table de la journée sélectionnée
        echo '<table>';

// on récupère les matches de la journée
        $sql2 = 'SELECT * FROM matches WHERE code_competition = \'' . $compet . '\' AND journee = \'' . $journee . '\'';
        $req2 = mysql_query($sql2) or die('Erreur SQL !<br>' . $sql2 . '<br>' . mysql_error());
        while ($data2 = mysql_fetch_assoc($req2)) {            //BOUCLE AFFICHAGE MATCHES
// Mise en variable des datas récoltées
            $match = $data2['code_match'];
            $horaire = $data2['heure_reception'];
            $date = date_fr($data2['date_reception']);
            $id_equipe_dom = $data2['id_equipe_dom'];
            $id_equipe_ext = $data2['id_equipe_ext'];
            $score1 = $data2['score_equipe_dom'];
            $score2 = $data2['score_equipe_ext'];
            $set_1_dom = $data2['set_1_dom'];
            $set_2_dom = $data2['set_2_dom'];
            $set_3_dom = $data2['set_3_dom'];
            $set_4_dom = $data2['set_4_dom'];
            $set_5_dom = $data2['set_5_dom'];
            $set_1_ext = $data2['set_1_ext'];
            $set_2_ext = $data2['set_2_ext'];
            $set_3_ext = $data2['set_3_ext'];
            $set_4_ext = $data2['set_4_ext'];
            $set_5_ext = $data2['set_5_ext'];
            $gagnea5_dom = $data2['gagnea5_dom'];
            $gagnea5_ext = $data2['gagnea5_ext'];
            $report = $data2['report'];
            $div = $data2['division'];

// Traitement des cellules vides si nulles
            if ($score1 == 0 && $score2 == 0) {
                $score1 = "&nbsp;";
                $score2 = "&nbsp;";
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

//Traitement de l'affichage des matches gagnés
            $class_dom = "equipes_dom";
            $class_ext = "equipes_ext";
            if ($score1 > $score2) {
                $class_dom = "equipes_dom_gagne";
            }
            if ($score1 < $score2) {
                $class_ext = "equipes_ext_gagne";
            }

//Traitement de l'affichage des matches reportés
            $class_report = "date";
            if ($report == 1) {
                $class_report = "date_report";
            }

// On regarde si on est en administrateur et si un match est sélectionné en modification
            if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin" && isset($_GET['m']) && $_GET['m'] == $match) {              //BOUCLE ADMINISTRATEUR MODIFICATION
                echo'<tr><td colspan="14">';
                echo'<table class="admin">';
                echo'  <form name="modif_match" action="includes/traitement.php?a=mr" method="post">';
                echo'   <tr>';
                echo'		<td>&nbsp;</td>';
                echo'		<td class="code_match">' . $match . '</td>';
                echo'		<td class="heure"><input value="' . $horaire . '" name="heure_reception" type="text" size="5" maxlength="5" /></td>';
                echo'		<td class="' . $class_report . '"><input value="' . $date . '" name="date_reception" type="text" size="8" maxlength="10" /></td>';
                echo'		<td class="' . $class_dom . '">' . recup_nom_equipe($compet, $id_equipe_dom) . '</td>';
                echo'		<td class="score"><input value="' . $score1 . '" name="score_equipe_dom" type="text" size="1" maxlength="1" /></td>';
                echo'		<td class="score">/</td>';
                echo'		<td class="score"><input value="' . $score2 . '" name="score_equipe_ext" type="text" size="1" maxlength="1" /></td>';
                echo'		<td class="' . $class_ext . '">' . recup_nom_equipe($compet, $id_equipe_ext) . '</td>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_1_dom . '" name="set_1_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_1_ext . '" name="set_1_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td colspan="6">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_2_dom . '" name="set_2_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_2_ext . '" name="set_2_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td colspan="6">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_3_dom . '" name="set_3_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_3_ext . '" name="set_3_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td colspan="6">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_4_dom . '" name="set_4_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_4_ext . '" name="set_4_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td colspan="6">&nbsp;</td>';
                echo'	  </tr>';
                echo'	  <tr>';
                echo'		<td colspan="5">&nbsp;</td>';
                echo'		<td><input value="' . $set_5_dom . '" name="set_5_dom" type="text" size="1" maxlength="2" /></td><td class="score">-</td>';
                echo'		<td><input value="' . $set_5_ext . '" name="set_5_ext" type="text" size="1" maxlength="2" /></td>';
                echo'		<td>&nbsp;</td>';
                echo'		<td colspan="5"><input value="Modifier" name="submit" class="submit" type="submit" /></td>';
                echo'	  </tr>';
                echo'	<input value="' . $data2['certif'] . '" name="certif" type="hidden" />';
                echo'	<input value="' . $match . '" name="code_match" type="hidden" />';
                echo'	<input value="' . $compet . '" name="compet" type="hidden" />';
                echo'	<input value="' . $div . '" name="division" type="hidden" />';
                echo'	<input value="' . $id_equipe_dom . '" name="id_equipe_dom" type="hidden" />';
                echo'	<input value="' . $id_equipe_ext . '" name="id_equipe_ext" type="hidden" />';
                echo'	<input value="' . $date . '" name="date_originale" type="hidden" />';
                echo'  </form>';
                echo'</table>';
                echo'</td></tr>';
            }              //BOUCLE ADMINISTRATEUR MODIFICATION FIN
            else {              //BOUCLE AFFICHAGE MATCH NON ADMINISTRATEUR
// Affichage des valeurs
                echo '   <tr>';
                echo '		<td>&nbsp;</td>';
                echo '		<td class="code_match">' . $match . '</td>';
                echo '		<td class="heure">' . $horaire . '</td>';
                echo '		<td class="' . $class_report . '">' . $date . '</td>';
                echo '		<td class="' . $class_dom . '">' . recup_nom_equipe($compet, $id_equipe_dom) . '</td>';
                echo '		<td class="score">' . $score1 . '</td>';
                echo '		<td class="score">/</td>';
                echo '		<td class="score">' . $score2 . '</td>';
                echo '		<td class="' . $class_ext . '">' . recup_nom_equipe($compet, $id_equipe_ext) . '</td>';
                echo '		<td class="sets">' . $set1 . '</td>';
                echo '		<td class="sets">' . $set2 . '</td>';
                echo '		<td class="sets">' . $set3 . '</td>';
                echo '		<td class="sets">' . $set4 . '</td>';
                echo '		<td class="sets">' . $set5 . '</td>';

// ***** SPECIFIQUE ADMINISTRATION ***********************************************
                if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {              //BOUCLE ADMINISTRATEUR GESTION MATCHES
//Traitement de l'affichage des matches certifiés
                    $certif = $data2['certif'];
                    if ($certif == 1) {
                        $certif = "";
                    } else {
                        $certif = '<a href="includes/traitement.php?a=cm&m=' . $match . '"><img src="images/certified.png" title="Certifier avoir reçu la feuille de ce match"  onclick="return confirm(\'Certifier le match ' . $match . ' ?\');" /></a>';
                    }
                    echo '	<td class="admin">' . $certif . '<a href="?a=mr&c=' . $compet . '&d=' . $div . '&m=' . $match . '"><img src="images/modif.gif" title="Modifier le score du match" /></a><a href="includes/traitement.php?a=sm&m=' . $match . '" onclick="return confirm(\'Cette opération entrainera irrémédiablement la suppression de ce match ! Êtes-vous sur de vouloir continuer ?\');"><img src="images/delete.gif" title="Supprimer ce match" /></a></td>';
                }              //BOUCLE ADMINISTRATEUR GESTION MATCHES FIN
// ***** FIN SPECIFIQUE ADMINISTRATION *******************************************

                echo '	</tr>';
            }              //BOUCLE AFFICHAGE MATCH NON ADMINISTRATEUR FIN
        }            //BOUCLE AFFICHAGE MATCHES FIN
// on ferme la table de la journée sélectionnée
        echo '</table>';
    }            //BOUCLE NOMBRE JOURNEE FIN
// Fermeture de la connexion à mysql 
//mysql_close(); 
}

//************************************************************************************************
//************************************************************************************************
function cryptage($Texte, $Cle)
//************************************************************************************************
/*
 * * Fonction    : cryptage 
 * * Input       : $text
 * * Output      : expression cryptée 
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
 * * Output      : expression decryptée 
 * * Description : DéCrypte une expression
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
 * * Output      : expression decryptée 
 * * Description : DéCrypte une expression
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
//Connexion à la base
    conn_db();

// on récupère les infos de la table SQL
    $sql = 'SELECT * FROM commission';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
// si aucune photo n'est présente on affiche l'image inconnue
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
// Fermeture de la connexion à mysql 
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
 * * Description : affichage de l'annuaire des équipes
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 22/04/2010 
 */ {
//Connexion à la base
    conn_db();

// on récupère les competitions dans la table equipes
    $sql = 'SELECT DISTINCT(code_competition) FROM equipes';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_assoc($req)) {
        $code_competition = $data['code_competition'];
        // pour chaque enregistrement trouvé on récupère le libellé
        $sql_libelle = 'SELECT libelle FROM competitions WHERE code_competition = \'' . $code_competition . '\'';
        $req_libelle = mysql_query($sql_libelle) or die('Erreur SQL !<br>' . $sql_libelle . '<br>' . mysql_error());
        $data_libelle = mysql_fetch_assoc($req_libelle);
        $libelle_compet = $data_libelle['libelle'];

        echo'<div class="competition">';
        echo'	<h1>' . $libelle_compet . '</h1>';

        // pour chaque compétition on récupère les divisions
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

            // pour chaque division on cherche les équipes et on les affiche
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
// Fermeture de la connexion à mysql 
//mysql_close(); 
}

function getPlayersFromTeam($id_equipe) {
    $players = array();
    conn_db();
    $sql = "SELECT CONCAT(prenom, ' ', nom) AS player FROM joueurs WHERE id_equipe = $id_equipe";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
        $players[] = $data['player'];
    }
    return $players;
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
        $joueurs = implode(',', getPlayersFromTeam($id_equipe));

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
        if (estMemeClassement($id_equipe)) {
            echo'		<td class="datas_details">' . $responsable . '<td>';
        } else {
            echo'		<td class="datas_details">Accessible aux utilisateurs connectés et évoluant dans le même championat<td>';
        }
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Téléphone 1 :</td>';
        if (estMemeClassement($id_equipe)) {
            echo'		<td class="datas_details">' . $telephone_1 . '<td>';
        } else {
            echo'		<td class="datas_details">Accessible aux utilisateurs connectés et évoluant dans le même championat<td>';
        }
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Téléphone 2 :</td>';
        if (estMemeClassement($id_equipe)) {
            echo'		<td class="datas_details">' . $telephone_2 . '<td>';
        } else {
            echo'		<td class="datas_details">Accessible aux utilisateurs connectés et évoluant dans le même championat<td>';
        }
        echo'	  </tr>';
        echo'      <tr class="tr_130">';
        echo'		<td class="titre_details">Email :</td>';
        if (estMemeClassement($id_equipe)) {
            echo'		<td class="datas_details">' . $email . '<td>';
        } else {
            echo'		<td class="datas_details">Accessible aux utilisateurs connectés et évoluant dans le même championat<td>';
        }
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
        if (estMemeClassement($id_equipe)) {
            if (strlen($joueurs) !== 0) {
                echo'      <tr class="tr_130">';
                echo'		<td class="titre_details">Joueurs :</td>';
                echo'		<td class="datas_details">' . $joueurs . '<td>';
                echo'	  </tr>';
            }
        }
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
        echo'<span><a href="includes/traitement.php?a=deconn">Se déconnecter</a></span></li>';
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
// On affiche le div si on est connecté en admin
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        echo'<div id="bottom">';
        echo'  <ul>';
        echo'    <li>';
        echo'      <span><img src="images/logo_equipe.png" title="Nouvelle équipe" alt="Nouvelle équipe"/></span>';
        echo'      <span><img src="images/logo_equipe_s.png" title="Supprimer une équipe" alt="Supprimer une équipe" /></span>';
        echo'      <span><img src="images/n_compet.png" title="Nouvelle compétition" alt="Nouvelle compétition"/></span>';
        echo'      <span><img src="images/s_compet.png" title="Supprimer une compétition" alt="Supprimer une compétition"/></span>';
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
// On affiche le div si on est connecté en admin
    if (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin") {
        echo'<div id="admin_page">';
        echo'  <ul>';
        echo'    <li><span><a href="includes/traitement.php?a=ie&c=' . $compet . '" target="_self"><img src="images/ajout.gif" title="Inscrire une équipe" alt="Inscrire équipe"/></span><span>Inscrire une équipe</span></li>';
        echo'  </ul>';
        echo'</div>';
    }
}

//************************************************************************************************
//************************************************************************************************
function envoi_mail($id1, $id2, $compet, $date, $num_envoi)
//************************************************************************************************
/*
 * * Fonction    : envoi_mail 
 * * Input       : $id1, $id2 et $num_envoi
 * * Output      : aucun 
 * * Description : Envoi un mail d'avertissement
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 16/11/2010
 */ {

// Création du mail
    $headers = 'From: "Laurent Gorlier"<laurent.gorlier@ufolep13volley.org>' . "\n";
    $headers .='Reply-To: laurent.gorlier@ufolep13volley.org' . "\n";
    $headers .='Cc: laurent.gorlier@ufolep13volley.org' . "\n";
    $headers .='Bcc: jean-marc.bernard.prestataire@gcetech.caisse-epargne.fr' . "\n";
    $headers .='Content-Type: text/html; charset="iso-8859-1"' . "\n";
    $headers .='Content-Transfer-Encoding: 8bit';

// Création du message
    $message = '<html><head><title>Saisie Internet des résultats</title></head><body>';
    $message = $message . 'Aux équipes de ' . recup_nom_equipe($compet, $id1) . ' et ' . recup_nom_equipe($compet, $id2) . '<BR>';
    $message = $message . 'Comme vous avez dû le lire sur le règlement, la saisie des informations sur le site internet doit être rigoureuse (pour le suivi de la commission Volley et pour l\'intérêt qu\'y portent les joueurs)<BR><BR>';
    $message = $message . 'Pour résumer, sur le site, 10 jours après la date indiquée pour le match (qui peut être en rouge si le match a été reportée), il doit y avoir un résultat affiché.<BR><BR>';
    $message = $message . 'Pour votre match du <b>' . $date . '</b> cela n\'est pas le cas. Puisqu\'il s\'agit d\'un premier message d\'alerte, nous vous donnons un délai supplémentaire de 5 jours pour que :<BR>';
    $message = $message . '- soit le résultat soit indiqué<BR>';
    $message = $message . '- soit une autre date de match soit affichée (pour cela il faut me la communiquer en tant que responsable des classements)<BR><BR>';
    $message = $message . 'Je vous rappelle que les deux équipes doivent veiller à ce que cette règle soit suivie ; les deux pourraient donc être pénalisées.<BR><BR>';
    $message = $message . 'Cordialement<BR><BR>Laurent Gorlier<BR>Responsable des classements<BR>';
    $message = $message . '</body></html>';

// Initialisation des destinataires
    $dest = recup_mail_equipe($id1) . "," . recup_mail_equipe($id2);

// Envoi du mail
    mail($dest, "[Ufolep 13 Volley] Saisie Internet des résultats", $message, $headers);
}

//************************************************************************************************
//************************************************************************************************
function affich_portail_equipe($id)
//************************************************************************************************
/*
 * * Fonction    : affich_portail_equipe
 * * Input       : STRING $id, id de l'équipe
 * * Output      : aucun 
 * * Description : Affiche le portail de l'équipe qui vient de se connecter.
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 27/04/2010 
 */ {//1
//Connexion à la base
    conn_db();

// Affectation à une variable de l'ID de l'équipe
    $id_equipe = $_SESSION['id_equipe'];
    if ($_SESSION['id_equipe'] == "admin") {
        die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
    }

// Récupération du nom de l'équipe
    $sql = 'SELECT * from equipes WHERE id_equipe = \'' . $id_equipe . '\' LIMIT 1';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

    if (mysql_num_rows($req) > 0) {    // si la requête comporte un résultat
        $data = mysql_fetch_assoc($req);
        $nom_equipe = $data['nom_equipe'];  // et on récupère la valeur nom_equipe que l'on affecte à une variable
        $compet = $data['code_competition'];    // on récupère aussi la valeur du code de competition
    }
//====================================================================
// on affiche le lien "se déconnecter" 
//====================================================================
    affich_connecte();

//====================================================================
// Titre de l'accueil
//====================================================================
    echo'<h1>' . $nom_equipe . ' - Vos matches </h1>';

//====================================================================
// On regarde à quelles compétitions l'équipe est inscrite
//====================================================================
    $sql = 'SELECT DISTINCT code_competition from matches WHERE id_equipe_dom = \'' . $id_equipe . '\' OR id_equipe_ext = \'' . $id_equipe . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
//====================================================================
// On affiche les matches de la compétition en question
//====================================================================
        affich_matches_equipe($id_equipe, $data['code_competition']);
    }

//====================================================================
// On affiche les détails de l'équipe
//====================================================================
    echo'<div id="details_equipe">';
    echo'<a name="me"></a>';
    affich_details_equipe($id_equipe, $compet);
    echo'<p><span><A href="javascript:popup(\'change.php?a=me&i=' . $id_equipe . '&c=' . $compet . '\')">Modifier les informations</A></span></p>';
    echo'</div>';
    echo'</div>';
}

//************************************************************************************************
//************************************************************************************************
function affich_matches_equipe($id_equipe, $compet)
//************************************************************************************************
/*
 * * Fonction    : affich_matches_champ
 * * Input       : $id_equipe id de l'équipe à modifier
 * * Output      : aucun 
 * * Description : Affiche les matches de championnat  d'une équipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 06/05/2010 
 */ {
//Connexion à la base
    conn_db();

// On récupère le libellé de la compétition
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
    echo '		<th colspan="5">Détails de sets</td>';
    echo '		<th>&nbsp;</td>';
    echo '	</tr>';

//====================================================================
// Récupération de la liste des matchs de l'équipe
//====================================================================
    $sql = 'SELECT * FROM matches WHERE (id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\') OR (id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\') ORDER BY journee ASC';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
//====================================================================
// Si on est en mode modification on affiche le détail du match
//====================================================================
        if (isset($_GET['i']) && !empty($_GET['i']) && $_GET['i'] == $data['code_match']) {

//====================================================================
// Si on n'est pas en mode Admin on désactive certains contrôles 
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
            echo'		  <td class="champ_modif_score">2ième Set</td><td>' . $set_2_dom . '</td><td>-</td><td>' . $set_2_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td colspan="3"></td>';
            echo'		  <td class="champ_modif_score">3ième Set</td><td>' . $set_3_dom . '</td><td>-</td><td>' . $set_3_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td class="modif_score_gras">Reporté</td><td class="data_modif_score">' . $report . '</td><td>&nbsp;</td>';
            echo'		  <td class="champ_modif_score">4ième Set</td><td>' . $set_4_dom . '</td><td>-</td><td>' . $set_4_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td class="modif_score_gras">Certifié</td><td class="data_modif_score">' . $certif . '</td><td>&nbsp;</td>';
            echo'		  <td class="champ_modif_score">5ième Set</td><td>' . $set_5_dom . '</td><td>-</td><td>' . $set_5_ext . '</td><td>&nbsp;</td><td>&nbsp;</td>';
            echo'		</tr>';
            echo'		<tr><td colspan="15">&nbsp;</td></tr>';
            echo'		<tr>';
            echo'		  <td>&nbsp;</td><td colspan="2">&nbsp;</td><td colspan="2" class="champ_modif_score">Match gagné à 5</td><td class="champ_modif_score">' . $gagnea5_dom . '</td>';
            echo'		  <td>&nbsp;</td><td>' . $gagnea5_ext . '</td><td>&nbsp;</td><td><input value="Valider" name="valider" class="submit" id="valider" type="submit" /></td>';
            echo'		</tr>';
            echo'		<tr><td colspan="3">(*) Réservé aux administrateurs</td><td colspan="12">&nbsp;</td></tr>';
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
//Traitement de l'affichage des matches gagnés
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
//Traitement de l'affichage des matches reportés
//====================================================================

            $class_report = "date";
            if ($report == 1) {
                $class_report = "date_report";
            }

//====================================================================
// Traitement des feuilles de matches certifiées
//====================================================================
            if ($certif == '1') {
                $certif = '<img src="images/certif.gif" title="Feuille de match reçue et certifiée" />';
            } else {
                $certif = '<a href="portail.php?a=ms&i=' . $code_match . '" target="_self"><img src="images/modif.gif" width="17" height="19" title="Modifier le score" /></a>';
            }

//====================================================================
// Traitement des liens http vers feuille de match des équipes
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
 * * Input       : $id_equipe id de l'équipe à modifier
 * * Output      : aucun 
 * * Description : Modifie les informations d'une équipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 04/05/2010 
 */ {
//Connexion à la base
    conn_db();

// Affectation à une variable de l'ID de l'équipe
    $id_equipe = $_SESSION['id_equipe'];
    $nom_equipe = recup_nom_equipe($id_equipe, $compet);

// On crée le début du formulaire
    echo'  <form id="modif_equipe" action="includes/traitement.php?a=me" method="post">';
    echo'    <h1>Modification de l\'équipe ' . $nom_equipe . '</h1>';
    echo'    <table>';

// on exécute la requête
    $sql = 'SELECT * FROM `details_equipes`  WHERE `id_equipe` = \'' . $id_equipe . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    while ($data = mysql_fetch_array($req)) {
//on affiche les données
        echo'      <tr>';
        echo'		<td>Responsable :</td>';
        echo'		<td><input value="' . $data['responsable'] . '" name="responsable" type="text" size="50" maxlength="50" /></td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Téléphone 1 :</td>';
        echo'		<td><input value="' . $data['telephone_1'] . '" name="telephone_1" type="text" size="50" maxlength="14" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Téléphone 2 :</td>';
        echo'		<td><input value="' . $data['telephone_2'] . '" name="telephone_2" type="text" size="50" maxlength="14" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Email :</td>';
        echo'		<td><input value="' . $data['email'] . '" name="email" type="text" size="50" maxlength="50" /><td>';
        echo'	  </tr>';
        echo'      <tr>';
        echo'		<td>Réception le :</td>';
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
}

//1
?>