<?php

try {
    require_once '../classes/MatchManager.php';
    $manager = new MatchManager();
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'GET':
            $manager->download();
            break;
        default:
            throw new Exception("Request not allowed");
    }
    echo json_encode(array("success" => true, "msg" => "Operation OK"));
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "msg" => $exc->getMessage()
    ));
}
