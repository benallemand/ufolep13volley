
<?php

require_once "../includes/fonctions_inc.php";

$idPlayers = filter_input(INPUT_POST, 'id_players');
$idTeam = filter_input(INPUT_POST, 'id_team');
$success = addPlayersToTeam($idPlayers, $idTeam);
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
