<?php

include("fonctions_inc.php");

if (isset($_GET['a']) && !empty($_GET['a'])) {
    $action = $_GET['a'];
}
if ($action == "auth") {
    auth();
} elseif ($action == "deconn") {
    deconn();
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
            } else {
                $_SESSION['id_equipe'] = $id_equipe;
            }      // Sinon c'est une équipe
            die('<META HTTP-equiv="refresh" content=0;URL=../portail.php>');
            mysql_close();
        }
    }
}
