
<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";
$success = saveUser();
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
