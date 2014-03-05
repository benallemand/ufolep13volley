<?php

require_once "../includes/fonctions_inc.php";
$results = array();
conn_db();
$sql = 'SELECT DISTINCT(code_competition) FROM classements';
$req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
while ($data = mysql_fetch_assoc($req)) {
    $code_competition = $data['code_competition'];
    $sql_libelle = 'SELECT libelle FROM competitions WHERE code_competition = \'' . $code_competition . '\'';
    $req_libelle = mysql_query($sql_libelle) or die('Erreur SQL !<br>' . $sql_libelle . '<br>' . mysql_error());
    $data_libelle = mysql_fetch_assoc($req_libelle);
    $libelle_compet = $data_libelle['libelle'];
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
        $sql_equipe = 'SELECT equipes.nom_equipe, equipes.id_equipe FROM classements, equipes WHERE classements.code_competition = \'' . $code_competition . '\' AND classements.division = \'' . $division . '\' AND classements.id_equipe = equipes.id_equipe';
        $req_equipe = mysql_query($sql_equipe) or die('Erreur SQL !<br>' . $sql_equipe . '<br>' . mysql_error());
        while ($data_equipe = mysql_fetch_assoc($req_equipe)) {
            $nom_equipe = $data_equipe['nom_equipe'];
            $id_equipe = $data_equipe['id_equipe'];
            $results[] = array(
                'code_competition' => $code_competition,
                'libelle_competition' => $libelle_compet,
                'division' => $nom_division,
                'id_equipe' => $id_equipe,
                'nom_equipe' => $nom_equipe
            );
        }
    }
}
echo json_encode($results);
?>
