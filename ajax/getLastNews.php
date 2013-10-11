<?php

require_once "../includes/fonctions_inc.php";

conn_db();
/** Format UTF8 pour afficher correctement les accents */
mysql_query("SET NAMES UTF8");
$sql = "SELECT * from news ORDER BY id_news DESC LIMIT 8";
$req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
$results = array();
while ($data = mysql_fetch_assoc($req)) {
    $results[] = $data;
}
echo json_encode($results);
?>
