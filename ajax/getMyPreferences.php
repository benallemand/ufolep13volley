<?php

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'GET':
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
            throw new Exception("Get my preferences not allowed for $profile !");
        case 'RESPONSABLE_EQUIPE':
        default:
            break;
    }
    echo json_encode($manager->getMyPreferences());
    exit();
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "msg" => $exc->getMessage()
    ));
}
