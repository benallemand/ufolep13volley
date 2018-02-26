<?php
$function_name = pathinfo(__FILE__, PATHINFO_FILENAME);
require_once "../includes/fonctions_inc.php";
try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'POST':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    if (function_exists($function_name)) {
        call_user_func($function_name);
    } else {
        throw new Exception("La fonction $function_name n'existe pas !");
    }
    echo json_encode(array(
        'success' => true,
        'message' => 'Modification OK'
    ));
} catch (Exception $exc) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array(
        "success" => false,
        "message" => $exc->getMessage()
    ));
}
