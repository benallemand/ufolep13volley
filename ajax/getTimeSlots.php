<?php

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'GET':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    require_once __DIR__ . '/../classes/TimeSlotManager.php';
    $manager = new TimeSlotManager();
    $userDetails = $manager->getCurrentUserDetails();
    $profile = $userDetails['profile_name'];
    $id_team = $userDetails['id_equipe'];
    $query = null;
    switch ($profile) {
        case 'ADMINISTRATEUR':
            break;
        case 'RESPONSABLE_EQUIPE':
        default:
            $query = "c.id_equipe = $id_team";
            break;
    }
    echo json_encode($manager->getTimeSlots($query));
    exit();
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "msg" => $exc->getMessage()
    ));
}
