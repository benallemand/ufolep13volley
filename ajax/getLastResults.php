<?php

require_once "../includes/fonctions_inc.php";

conn_db();
$sql = "select 
c.libelle AS competition, 
m.division, 
e1.nom_equipe AS equipe_domicile, 
e2.nom_equipe AS equipe_exterieur, 
CONCAT(m.set_1_dom, '-', set_1_ext) AS set1, 
CONCAT(m.set_2_dom, '-', set_2_ext) AS set2, 
CONCAT(m.set_3_dom, '-', set_3_ext) AS set3, 
CONCAT(m.set_4_dom, '-', set_4_ext) AS set4, 
CONCAT(m.set_5_dom, '-', set_5_ext) AS set5, 
m.date_reception
from matches m
left join competitions c on c.code_competition =  m.code_competition
left join equipes e1 on e1.id_equipe =  m.id_equipe_dom
left join equipes e2 on e2.id_equipe =  m.id_equipe_ext
order by date_reception DESC LIMIT 20";
$req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
$results = array();
while ($data = mysql_fetch_assoc($req)) {
    $results[] = $data;
}
echo json_encode($results);
?>
