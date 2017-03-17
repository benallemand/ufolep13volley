<?php

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'GET':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    require_once '../classes/TeamManager.php';
    $manager = new TeamManager();
    $userDetails = $manager->getCurrentUserDetails();
    $profile = $userDetails['profile_name'];
    $id_team = $userDetails['id_equipe'];
    switch ($profile) {
        case 'ADMINISTRATEUR':
            throw new Exception("Get my team not allowed for administrateur !");
        case 'RESPONSABLE_EQUIPE':
        default:
            break;
    }
    echo json_encode(array($manager->getTeam($id_team)));
    exit();
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "msg" => $exc->getMessage()
    ));
}

