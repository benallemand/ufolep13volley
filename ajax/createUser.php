
<?php

require_once "../includes/fonctions_inc.php";

$idTeam = filter_input(INPUT_POST, 'id_equipe');
$login = filter_input(INPUT_POST, 'login');
$email = filter_input(INPUT_POST, 'email');
$success = createUser($login, $email, $idTeam);
echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
