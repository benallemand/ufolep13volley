<?php

require_once "../includes/fonctions_inc.php";
try {
    saveTeam();
} catch (Exception $ex) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Erreur durant la modification: ' . $ex->getMessage()
    ));
    return;
}
echo json_encode(array(
    'success' => true,
    'message' => 'Modification OK'
));
return;
