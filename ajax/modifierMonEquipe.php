
<?php

require_once "../includes/fonctions_inc.php";

$success = modifyMyTeam();
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
