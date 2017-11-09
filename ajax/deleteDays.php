<?php

require_once "../includes/fonctions_inc.php";

try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'POST':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    $ids = filter_input(INPUT_POST, 'ids');
    deleteDays($ids);
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

