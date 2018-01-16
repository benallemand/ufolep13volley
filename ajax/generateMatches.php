<?php

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'POST':
            $code_competition = filter_input(INPUT_POST, 'code_competition');
            break;
        case 'GET':
            $code_competition = filter_input(INPUT_GET, 'code_competition');
            break;
        default:
            throw new Exception("Request not allowed");
    }
    require_once '../classes/MatchManager.php';
    $manager = new MatchManager();
    $manager->generateMatches($code_competition);
} catch (Exception $ex) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Erreur durant la modification: ' . $ex->getMessage()
    ));
    return;
}
echo json_encode(array(
    'success' => true,
    'message' => 'Modification OK'
));
