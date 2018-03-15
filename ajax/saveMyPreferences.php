<?php

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'POST':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    require_once __DIR__ . '/../classes/UserManager.php';
    $manager = new UserManager();
    $userDetails = $manager->getCurrentUserDetails();
    $profile = $userDetails['profile_name'];
    $id_team = $userDetails['id_equipe'];
    switch ($profile) {
        case 'ADMINISTRATEUR':
            throw new Exception("modify password not allowed for $profile !");
        case 'RESPONSABLE_EQUIPE':
        default:
            break;
    }
    $manager->saveMyPreferences();
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "msg" => $exc->getMessage()
    ));
}
echo json_encode(array(
    'success' => true,
    'message' => 'Modification OK'
));
