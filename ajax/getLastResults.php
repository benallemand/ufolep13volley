<?php

require_once "../includes/fonctions_inc.php";

conn_db();
/** Format UTF8 pour afficher correctement les accents */
if ($_SERVER['SERVER_NAME'] !== 'localhost') {
    mysql_query("SET NAMES UTF8");
}
$sql = "select 
c.libelle AS competition, 
IF(c.code_competition='f' OR c.code_competition='m', CONCAT('Division ', m.division, ' - ', j.nommage), j.nommage) AS division_journee, 
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
    }
    $results[] = $data;
}
echo json_encode($results);
?>
