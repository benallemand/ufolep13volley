<?php
include("includes/fonctions_inc.php");
//Connexion à la base
conn_db();
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // on récupère les infos de la table SQL
    $sql = 'SELECT fdm FROM details_equipes where id_equipe = \'' . $_GET['id'] . '\'';
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    if (mysql_num_rows($req) > 0) {   // si la requête comporte un résultat dans la table compte_acces
        $data = mysql_fetch_assoc($req);
        $file = $data['fdm'];
    }
}

if (($file != "") && (file_exists("./fdm/" . basename($file)))) {
    $size = filesize("./fdm/" . basename($file));
    header("Content-Type: application/force-download; name=" . basename($file));
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: $size");
    header("Content-Disposition: attachment; filename=" . basename($file));
    header("Expires: 0");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    readfile("./fdm/" . basename($file));
    exit();
} else {
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
    <HTML>
        <HEAD>
            <TITLE>UFOLEP 13 VOLLEY</TITLE>
            <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
            <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        </HEAD>
        <body onload="window.opener = self; window.setTimeout(\'self.close()\',3000)">
            <H1>Aucun document ne correspond à votre demande.</H1><BR/><BR/>
            Cette fenêtre va se fermer automatiquement dans 3 secondes ...
        </body>';
    <?php
}
// on ferme sql 
mysql_close();
?>
