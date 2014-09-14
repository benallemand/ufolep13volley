
<?php

require_once "../includes/fonctions_inc.php";

$id = filter_input(INPUT_POST, 'id');
$success = removeTimeSlot($id);
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
