
<?php

require_once "../includes/fonctions_inc.php";

$success = sendMailNextMatches();
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Envoi OK' : 'Erreur'
));
