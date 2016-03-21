<?php

require_once "../includes/fonctions_inc.php";
try {
    ini_set('upload_max_filesize', '1M');
    savePlayer();
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
