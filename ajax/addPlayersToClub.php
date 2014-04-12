
<?php

require_once "../includes/fonctions_inc.php";

$idPlayers = filter_input(INPUT_POST, 'id_players');
$idClub = filter_input(INPUT_POST, 'id_club');
$success = addPlayersToClub($idPlayers, $idClub);
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
