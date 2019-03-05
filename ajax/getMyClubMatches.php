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
    require_once __DIR__ . '/../classes/TeamManager.php';
    $team_manager = new TeamManager();
    $team_details = $team_manager->getTeam($id_team);
    $id_club = $team_details['id_club'];
    switch ($profile) {
        case 'ADMINISTRATEUR':
            throw new Exception("Get my club matches allowed only for RESPONSABLE_EQUIPE !");
        case 'RESPONSABLE_EQUIPE':
        default:
            $query = "(m.id_equipe_dom IN (SELECT id_equipe 
                                          FROM equipes 
                                          WHERE id_club = $id_club) 
                      OR m.id_equipe_ext IN (SELECT id_equipe 
                                             FROM equipes 
                                             WHERE id_club = $id_club))
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
