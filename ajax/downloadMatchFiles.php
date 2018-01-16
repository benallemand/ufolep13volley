<?php

try {
    require_once '../classes/MatchManager.php';
    $manager = new MatchManager();
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    $inputs = filter_input_array(INPUT_GET);
    switch ($requestMethod) {
        case 'GET':
            $manager->download($inputs);
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
