<?php

require_once "../includes/fonctions_inc.php";

$success = false;

$code_match = filter_input(INPUT_POST, 'code_match');
$reason = filter_input(INPUT_POST, 'reason');
$success = askForReport($code_match, $reason);

echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
