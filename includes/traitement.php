<?php

include("fonctions_inc.php");

if (isset($_GET['a']) && !empty($_GET['a'])) {
    $action = $_GET['a'];
}

if ($action == "am") {
    ajout_match();
}
if ($action == "an") {
    ajout_news();
}
if ($action == "cm") {
    certif_match();
}
if ($action == "gpa") {
    penalite("a");
}
if ($action == "gpe") {
    penalite("e");
}
if ($action == "ie") {
    inscrit_equipe();
}
if ($action == "me") {
    maj_equipe();
}
if ($action == "mn") {
    maj_news();
}
if ($action == "mr") {
    maj_result();
}
if ($action == "se") {
    suppr_equipe();
}
if ($action == "sec") {
    suppr_equipe_compet();
}
if ($action == "sm") {
    suppr_match();
}
if ($action == "sn") {
    suppr_news();
} elseif ($action == "admin_team") {
    admin_team();
} elseif ($action == "admin_journee") {
    admin_journee();
} elseif ($action == "auth") {
    auth();
} elseif ($action == "maj_team") {
    maj_team();
} elseif ($action == "deconn") {
    deconn();
} elseif ($action == "add_team") {
    add_team();
} elseif ($action == "maj_certif") {
    maj_certif();
}

//************************************************************************************************
function deconn()
//************************************************************************************************
/*
 * * Fonction    : deconn 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : Déconnecte la session de l'utilisateur
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 02/02/2009
 */ {
    session_destroy();
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
function ajout_match()
//************************************************************************************************
/*
 * * Fonction    : ajoute_match 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : ajoute un match dans une competition
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 12/05/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données du formulaire
    $compet = $_POST['code_competition'];
    $div = $_POST['division'];
    $code_match = $_POST['code_match'];
    $journee = $_POST['journee'];
    $heure_reception = $_POST['heure_reception'];
    $date_reception = date_uk($_POST['date_reception']);
    $id_equipe_dom = $_POST['id_equipe_dom'];
    $id_equipe_ext = $_POST['id_equipe_ext'];

    $sql = 'INSERT INTO matches(code_competition, division, code_match, id_equipe_dom, id_equipe_ext, date_reception, heure_reception, journee) VALUES(\'' . $compet . '\',\'' . $div . '\',\'' . $code_match . '\',\'' . $id_equipe_dom . '\',\'' . $id_equipe_ext . '\',\'' . $date_reception . '\',\'' . $heure_reception . '\',\'' . $journee . '\')';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function ajout_news()
//************************************************************************************************
/*
 * * Fonction    : ajout_news 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : ajoute une news dans la base de données
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 04/06/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données
    $date_news = date_uk($_POST['date_news']);
    $titre_news = addslashes($_POST['titre_news']);
    $texte_news = addslashes($_POST['texte_news']);

//Requête de MAJ
    $sqlmaj = 'INSERT INTO news(`date_news`,`titre_news`,`texte_news`) Values(\'' . $date_news . '\',\'' . $titre_news . '\',\'' . $texte_news . '\');';
    mysql_query($sqlmaj) or die('Erreur SQL !<br>' . $sqlmaj . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function auth()
//************************************************************************************************
/*
 * * Fonction    : auth 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : authentifie à la connexion d'un utilisateur
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 27/04/2010
 */ {
//Connexion à la base
    conn_db();

// on regarde si le login / mot de passe n'est pas vide
    if ((empty($_POST['login'])) || (empty($_POST['password']))) {
        mysql_close();
        die('<META HTTP-equiv="refresh" content=0;URL=../portail.php?log=noauth>');
    }

// on regarde si le login / mot de passe est valide
    $login = $_POST['login'];
    $password = addslashes($_POST['password']);

//Requête d'interrogation de la table comptes_acces
    $sql = 'SELECT * FROM comptes_acces WHERE login = \'' . $login . '\' LIMIT 1';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

    if (mysql_num_rows($req) > 0) {   // si la requête comporte un résultat dans la table compte_acces
        $data = mysql_fetch_assoc($req);
        if ($data['password'] != $password) {
            mysql_close();
            die('<META HTTP-equiv="refresh" content=0;URL=../portail.php?log=wpass');
        } else {
            $id_equipe = $data['id_equipe'];
            session_start();
            $_SESSION['login'] = $login;
            $_SESSION['password'] = $password;
            // Si l'ID_EQUIPE est égal à 999 on passe en admin !
            if ($id_equipe == "999") {
                $_SESSION['id_equipe'] = "admin";
                die('<META HTTP-equiv="refresh" content=0;URL=../index.php>');
            } else {
                $_SESSION['id_equipe'] = $id_equipe;
                die('<META HTTP-equiv="refresh" content=0;URL=../portail.php>');
            }      // Sinon c'est une équipe
            mysql_close();
        }
    }
}

//************************************************************************************************
//************************************************************************************************
function certif_match()
//************************************************************************************************
/*
 * * Fonction    : certif_match 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : supprime un match
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 12/05/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données
    $code_match = $_GET['m'];

//on mets à jour l'entrée dans la table matches
    $sql = 'UPDATE matches SET certif = 1 WHERE code_match = \'' . $code_match . '\'';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
function inscrit_equipe()
//************************************************************************************************
/*
 * * Fonction    : inscrit_equipe 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : supprime une equipe d'une competition
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 08/05/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données du formulaire
    $id_equipe = $_POST['id_equipe'];
    $compet = $_POST['compet'];
    $div = $_POST['div'];

    $sql = 'INSERT INTO classements(code_competition, division, id_equipe) VALUES(\'' . $compet . '\',\'' . $div . '\',\'' . $id_equipe . '\')';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function maj_equipe()
//************************************************************************************************
/*
 * * Fonction    : maj_equipe 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : mets à jour le table details_equipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 04/05/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données
    $id_equipe = $_POST['id_equipe'];
    $responsable = $_POST['responsable'];
    $telephone_1 = $_POST['telephone_1'];
    $telephone_2 = $_POST['telephone_2'];
    $email = $_POST['email'];
    $jour_reception = $_POST['jour_reception'];
    $heure_reception = $_POST['heure_reception'];
    $gymnase = $_POST['gymnase'];
    $localisation = $_POST['localisation'];
    $site_web = $_POST['site_web'];

//Requête de MAJ
    $sqlmaj = 'UPDATE details_equipes SET responsable = \'' . $responsable . '\', telephone_1 = \'' . $telephone_1 . '\', telephone_2 = \'' . $telephone_2 . '\', email = \'' . $email . '\', jour_reception = \'' . $jour_reception . '\', heure_reception = \'' . $heure_reception . '\', gymnase = \'' . $gymnase . '\', localisation = \'' . $localisation . '\', site_web = \'' . $site_web . '\' WHERE `id_equipe` = ' . $id_equipe . ' LIMIT 1;';

    mysql_query($sqlmaj) or die('Erreur SQL !<br>' . $sqlmaj . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function maj_news()
//************************************************************************************************
/*
 * * Fonction    : maj_news 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : mets à jour la table NEWS
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 04/06/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données
    $id_news = $_POST['id_news'];
    $texte_news = addslashes($_POST['texte_news']);
    $titre_news = addslashes($_POST['titre_news']);
    $date_news = date_uk($_POST['date_news']);

//Requête de MAJ
    $sqlmaj = 'UPDATE news SET texte_news = \'' . $texte_news . '\', titre_news = \'' . $titre_news . '\', date_news = \'' . $date_news . '\' WHERE `id_news` = ' . $id_news . ' LIMIT 1;';
    mysql_query($sqlmaj) or die('Erreur SQL !<br>' . $sqlmaj . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function maj_result()
//************************************************************************************************
/*
 * * Fonction    : maj_result 
 * * Input       : aucun -> Methode $_POST
 * * Output      : aucun 
 * * Description : mise à jour de la base de données
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 06/05/2010
 */ {
//Connexion à la base
    conn_db();

//On récupère les valeurs du formulaire de mise à jour ============================================================
    $score_equipe_dom = $_POST['score_equipe_dom'];
    $score_equipe_ext = $_POST['score_equipe_ext'];
    ;
    $set_1_dom = $_POST['set_1_dom'];
    $set_2_dom = $_POST['set_2_dom'];
    $set_3_dom = $_POST['set_3_dom'];
    $set_4_dom = $_POST['set_4_dom'];
    $set_5_dom = $_POST['set_5_dom'];
    if (empty($gagnea5_dom)) {
        $gagnea5_dom = 0;
    } else {
        $gagnea5_dom = $_POST['gagnea5_dom'];
    }
    $set_1_ext = $_POST['set_1_ext'];
    $set_2_ext = $_POST['set_2_ext'];
    $set_3_ext = $_POST['set_3_ext'];
    $set_4_ext = $_POST['set_4_ext'];
    $set_5_ext = $_POST['set_5_ext'];
    if (empty($gagnea5_ext)) {
        $gagnea5_ext = 0;
    } else {
        $gagnea5_ext = $_POST['gagnea5_ext'];
    }
    $code_match = $_POST['code_match'];
    $compet = $_POST['compet'];
    $division = $_POST['division'];
    $heure_reception = $_POST['heure_reception'];
    $date_reception = date_uk($_POST['date_reception']);

// Traitement des matches gagnés à 5 ==============================================================================
    if (empty($gagnea5_dom)) {
        $gagnea5_dom = 0;
    }
    if (empty($gagnea5_ext)) {
        $gagnea5_ext = 0;
    }

//Traitement des matches forfait ==================================================================================
    $total_sets_dom = $set_1_dom + $set_2_dom + $set_3_dom;
    $total_sets_ext = $set_1_ext + $set_2_ext + $set_3_ext;
    if ($total_sets_dom == 0 && $total_sets_ext == 75) {
        $forfait_dom = 1;
    } // Si le match est perdu par forfait pour l'équipe dom
    else {
        $forfait_dom = 0;
    }
    if ($total_sets_dom == 75 && $total_sets_ext == 0) {
        $forfait_ext = 1;
    } // Si le match est perdu par forfait pour l'équipe ext
    else {
        $forfait_ext = 0;
    }

//Traitement des reports
    if (isset($_POST['date_originale']) && !empty($_POST['date_originale'])) {
        if ($_POST['date_originale'] != $_POST['date_reception']) {
            $report = 1;
        } else {
            $report = 0;
        }
    }
    if (isset($_POST['heure_originale']) && !empty($_POST['heure_originale'])) {
        if ($_POST['heure_originale'] != $_POST['heure_reception']) {
            $report = 1;
        } else {
            $report = 0;
        }
    }

// Mise à jour de la table ========================================================================================
    $sql = 'UPDATE matches SET score_equipe_dom = \'' . $score_equipe_dom . '\', score_equipe_ext = \'' . $score_equipe_ext . '\', set_1_dom = \'' . $set_1_dom . '\', set_1_ext = \'' . $set_1_ext . '\', set_2_dom = \'' . $set_2_dom . '\', set_2_ext = \'' . $set_2_ext . '\', set_3_dom = \'' . $set_3_dom . '\', set_3_ext = \'' . $set_3_ext . '\', set_4_dom = \'' . $set_4_dom . '\', set_4_ext = \'' . $set_4_ext . '\', set_5_dom = \'' . $set_5_dom . '\', set_5_ext = \'' . $set_5_ext . '\', gagnea5_dom = \'' . $gagnea5_dom . '\', gagnea5_ext = \'' . $gagnea5_ext . '\', forfait_dom = \'' . $forfait_dom . '\', forfait_ext = \'' . $forfait_ext . '\', date_reception = \'' . $date_reception . '\', heure_reception = \'' . $heure_reception . '\', report = \'' . $report . '\' WHERE code_match = \'' . $code_match . '\'';

// on insère les informations du formulaire dans la table =========================================================
    mysql_query($sql) or die('Erreur SQL !' . $sql . '<br>' . mysql_error());

// Mise à jour de la table derniers résultats =====================================================================
// **************   A  F A I R E   ******************
// on insère les informations du formulaire dans la table =========================================================
//mysql_query($sql) or die('Erreur SQL !'.$sql.'<br>'.mysql_error()); 
//************************************************************************************************
// Mise à jour du classement
//************************************************************************************************
//On récupère l'ID des équipes pour lancer la fonction calcul_classement
    $id_equipe_dom = $_POST['id_equipe_dom'];
    $id_equipe_ext = $_POST['id_equipe_ext'];

// On calcule les points des équipes
    calcul_classement($id_equipe_dom, $compet, $division);
    calcul_classement($id_equipe_ext, $compet, $division);

    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function penalite($action)
//************************************************************************************************
/*
 * * Fonction    : penalite 
 * * Input       : $action
 * * Output      : aucun 
 * * Description : ajoute ou enlève un point de pénalité à une équipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 08/05/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données
    $id_equipe = $_GET['i'];
    $compet = $_GET['c'];

//On incrémente la valeur des pénalités de l'équipe
    $sql = 'SELECT penalite,division FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    if (mysql_num_rows($req) == 1) {
        $data = mysql_fetch_assoc($req);
        $penalite = $data['penalite'];
        $division = $data['division'];
    }
    if ($action == "a") {
        $penalite++;
    } elseif ($action == "e") {
        $penalite--;
    }

// Si $penalite est négatif on le positionne à 0
    if ($penalite < 0) {
        $penalite = 0;
    }

// on mets à jour la BDD
    $sqlmaj = 'UPDATE classements set penalite = \'' . $penalite . '\' WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    mysql_query($sqlmaj) or die('Erreur SQL !<br>' . $sqlmaj . '<br>' . mysql_error());

// On recalcule le classement de l'équipe
    calcul_classement($id_equipe, $compet, $division);

// Fermeture de la connexion à mysql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function suppr_equipe()
//************************************************************************************************
/*
 * * Fonction    : suppr_equipe 
 * * Input       : $ID de l'équipe
 * * Output      : aucun 
 * * Description : supprime une equipe
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 08/05/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données
    $id_equipe = $_GET['i'];

//on supprime l'entrée dans la table CLASSEMENTS
    $sql = 'DELETE FROM classements WHERE id_equipe = \'' . $id_equipe . '\'';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

//on supprime l'entrée dans la table COMPTES_ACCES
    $sql = 'DELETE FROM comptes_acces WHERE id_equipe = \'' . $id_equipe . '\'';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

//on supprime l'entrée dans la table EQUIPES
    $sql = 'DELETE FROM equipes WHERE id_equipe = \'' . $id_equipe . '\'';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

//on supprime l'entrée dans la table MATCHES
    $sql = 'DELETE FROM matches WHERE id_equipe_dom = \'' . $id_equipe . '\' OR id_equipe_ext = \'' . $id_equipe . '\'';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************

function suppr_equipe_compet()

//************************************************************************************************
/*
 * * Fonction    : suppr_equipe_compet 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : supprime une equipe d'une competition
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 08/05/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données
    $id_equipe = $_GET['i'];
    $compet = $_GET['c'];

//on supprime l'entrée dans la table CLASSEMENTS
    $sql = 'DELETE FROM classements WHERE id_equipe = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\'';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

//on supprime l'entrée dans la table MATCHES
    $sql = 'DELETE FROM matches WHERE (id_equipe_dom = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\') OR (id_equipe_ext = \'' . $id_equipe . '\' AND code_competition = \'' . $compet . '\')';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function suppr_match()
//************************************************************************************************
/*
 * * Fonction    : suppr_match 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : supprime un match
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 12/05/2010
 */ {
//Connexion à la base
    conn_db();

//On recueille les données
    $code_match = $_GET['m'];

//on supprime l'entrée dans la table CLASSEMENTS
    $sql = 'DELETE FROM matches WHERE code_match = \'' . $code_match . '\'';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}

//************************************************************************************************
//************************************************************************************************
function suppr_news()
//************************************************************************************************
/*
 * * Fonction    : suppr_news 
 * * Input       : aucun
 * * Output      : aucun 
 * * Description : supprime une news
 * * Creator     : Jean-Marc Bernard 
 * * Date        : 04/06/2010
 */ {

//Connexion à la base
    conn_db();

//On recueille les données
    $id_news = $_GET['i'];

//on supprime l'entrée dans la table CLASSEMENTS
    $sql = 'DELETE FROM news WHERE id_news = \'' . $id_news . '\'';
    mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());

// on ferme sql 
    mysql_close();

// On retourne à la page initiale 
    die('<META HTTP-equiv="refresh" content=0;URL=' . $_SERVER['HTTP_REFERER'] . '>');
}
