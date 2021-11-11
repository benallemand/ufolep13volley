<?php
$function_name = pathinfo(__FILE__, PATHINFO_FILENAME);
require_once __DIR__ . "/../classes/MatchManager.php";
try {
    $requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($requestMethod) {
        case 'POST':
            break;
        default:
            throw new Exception("Request not allowed");
    }
    $manager = new MatchManager();
    $manager->run_insert_matches_from_script();
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
