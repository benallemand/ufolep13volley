<?php

global $db;
require_once "../includes/fonctions_inc.php";
$results = array();
conn_db();
$sql = 'SELECT DISTINCT(code_competition) FROM classements';
$req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
while ($data = mysqli_fetch_assoc($req)) {
    $code_competition = $data['code_competition'];
    $sql_libelle = 'SELECT libelle FROM competitions WHERE code_competition = \'' . $code_competition . '\'';
    $req_libelle = mysqli_query($db, $sql_libelle) or die('Erreur SQL !<br>' . $sql_libelle . '<br>' . mysqli_error($db));
    $data_libelle = mysqli_fetch_assoc($req_libelle);
    $libelle_compet = $data_libelle['libelle'];
    $sql_division = 'SELECT DISTINCT(division) FROM classements WHERE code_competition = \'' . $code_competition . '\'';
    $req_division = mysqli_query($db, $sql_division) or die('Erreur SQL !<br>' . $sql_division . '<br>' . mysqli_error($db));
    while ($data_division = mysqli_fetch_assoc($req_division)) {
        if (mysqli_num_rows($req_division) < 2) {
            $division = $data_division['division'];
            $nom_division = "Unique";
        } else {
            $division = $data_division['division'];
            $nom_division = $data_division['division'];
        }
        $sql_equipe = 'SELECT equipes.nom_equipe, equipes.id_equipe FROM classements, equipes WHERE classements.code_competition = \'' . $code_competition . '\' AND classements.division = \'' . $division . '\' AND classements.id_equipe = equipes.id_equipe';
        $req_equipe = mysqli_query($db, $sql_equipe) or die('Erreur SQL !<br>' . $sql_equipe . '<br>' . mysqli_error($db));
        while ($data_equipe = mysqli_fetch_assoc($req_equipe)) {
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

