
<?php

require_once "../includes/fonctions_inc.php";
ini_set('upload_max_filesize', '1M');
$success = savePlayer();
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
