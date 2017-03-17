<?php

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'GET':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    require_once '../classes/UserManager.php';
    $manager = new UserManager();
    $userDetails = $manager->getCurrentUserDetails();
    $profile = $userDetails['profile_name'];
    $id_team = $userDetails['id_equipe'];
    switch ($profile) {
        case 'ADMINISTRATEUR':
        case 'RESPONSABLE_EQUIPE':
        default:
            break;
    }
    echo json_encode($manager->getActivity($id_team));
    exit();
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "msg" => $exc->getMessage()
    ));
}
