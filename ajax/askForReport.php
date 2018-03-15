<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'POST':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    $code_match = filter_input(INPUT_POST, 'code_match');
    $reason = filter_input(INPUT_POST, 'reason');
    askForReport($code_match, $reason);
    echo json_encode(array(
        'success' => true,
        'message' => 'Modification OK'
    ));
    exit();
} catch (Exception $exc) {
    echo json_encode(array(
        "success" => false,
        "message" => $exc->getMessage()
    ));
}