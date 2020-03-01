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
    $compet = filter_input(INPUT_GET, 'competition');
    $div = filter_input(INPUT_GET, 'division');
    if (!isset($compet)) {
        $query = "1 = 1 
                  ORDER BY m.code_match";
    } else {
        $query = "m.code_competition = '$compet' 
                  AND m.division = '$div'
                  AND m.match_status IN ('CONFIRMED', 'NOT_CONFIRMED') 
                  ORDER BY j.nommage, m.date_reception, m.code_match";
    }
    echo json_encode($manager->getMatches($query));
    exit();
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "msg" => $exc->getMessage()
    ));
}
