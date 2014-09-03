
<?php

require_once "../includes/fonctions_inc.php";

$ids = filter_input(INPUT_POST, 'ids');
$success = deletePlayers($ids);
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
