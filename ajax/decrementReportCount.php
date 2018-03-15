<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";

$success = false;

$compet = filter_input(INPUT_POST, 'compet');
$equipe = filter_input(INPUT_POST, 'equipe');
$success = decrementReportCount($compet, $equipe);

echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
