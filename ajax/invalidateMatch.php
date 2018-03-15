<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";

$code_match = filter_input(INPUT_POST, 'code_match');
$success = invalidateMatch($code_match);
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
