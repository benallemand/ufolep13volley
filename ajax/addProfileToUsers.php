
<?php

require_once "../includes/fonctions_inc.php";

$idUsers = filter_input(INPUT_POST, 'id_users');
$idProfile = filter_input(INPUT_POST, 'id_profile');
$success = addProfileToUsers($idProfile, $idUsers);
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
