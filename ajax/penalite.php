<?php

require_once "../includes/fonctions_inc.php";

$type = filter_input(INPUT_POST, 'type');
$success = false;
switch ($type) {
    case 'ajout':
        $compet = filter_input(INPUT_POST, 'compet');
        $equipe = filter_input(INPUT_POST, 'equipe');
        $success = addPenalty($compet, $equipe);
        break;
    case 'suppression':
        $compet = filter_input(INPUT_POST, 'compet');
        $equipe = filter_input(INPUT_POST, 'equipe');
        $success = removePenalty($compet, $equipe);
        break;

    default:
        break;
}
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
