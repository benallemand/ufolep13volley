<?php

require_once "../includes/fonctions_inc.php";

$success = false;

$code_match = filter_input(INPUT_POST, 'code_match');
$success = acceptReport($code_match);

echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
