<?php
try {
    $action_name = basename(
        parse_url(
            filter_input(INPUT_SERVER,
                'REQUEST_URI'),
            PHP_URL_PATH));
    require_once __DIR__ . "/$action_name.php";
    switch (filter_input(INPUT_SERVER, 'REQUEST_METHOD')) {
        case 'POST':
            $parameters = filter_input_array(INPUT_POST);
            call_user_func($action_name, $parameters);
            break;
        case 'GET':
            $parameters = filter_input_array(INPUT_GET);
            echo json_encode(call_user_func($action_name, $parameters));
            exit(0);
        case 'PUT':
        case 'DELETE':
        default:
            throw new Exception("Unsupported REQUEST_METHOD !");
    }
    echo json_encode(array(
        'success' => true,
        'message' => 'Modification OK'
    ));
} catch (Exception $exception) {
    echo json_encode(array(
        'success' => false,
        'message' => $exception->getMessage()
    ));
}