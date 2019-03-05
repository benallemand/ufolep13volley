<?php

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'GET':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    require_once __DIR__ . '/../classes/MatchManager.php';
    $manager = new MatchManager();
    $userDetails = $manager->getCurrentUserDetails();
    $profile = $userDetails['profile_name'];
    $id_team = $userDetails['id_equipe'];
    switch ($profile) {
        case 'ADMINISTRATEUR':
            throw new Exception("Get my matches allowed only for RESPONSABLE_EQUIPE !");
        case 'RESPONSABLE_EQUIPE':
        default:
            $query = "(m.id_equipe_dom = $id_team 
                      OR m.id_equipe_ext = $id_team)
                      AND m.match_status = 'CONFIRMED' 
                      ORDER BY j.nommage, m.date_reception, m.code_match";
            break;
    }
    echo json_encode($manager->getMatches($query));
    exit();
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "msg" => $exc->getMessage()
    ));
}
