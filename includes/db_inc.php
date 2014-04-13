<?php

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
    if (($_SERVER['SERVER_NAME'] !== 'localhost') && ($_SERVER['SERVER_NAME'] !== '82.228.19.67')) {
        mysql_query("SET NAMES UTF8");
    }
}
