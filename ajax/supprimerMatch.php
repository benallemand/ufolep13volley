
<?php

require_once "../includes/fonctions_inc.php";

$code_match = filter_input(INPUT_POST, 'code_match');
$success = removeMatch($code_match);
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
