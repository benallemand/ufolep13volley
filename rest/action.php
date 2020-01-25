<?php
$action_name = basename(filter_input(INPUT_SERVER, 'REQUEST_URI'));
switch (filter_input(INPUT_SERVER, 'REQUEST_METHOD')) {
    case 'POST':
        $parameters = filter_input_array(INPUT_POST);
        require_once __DIR__ . "/$action_name.php";
        call_user_func($action_name, $parameters);
        break;
    case 'GET':
    case 'PUT':
    case 'DELETE':
    default:
        throw new Exception("Unsupported REQUEST_METHOD !");
}
echo json_encode(array(
    'success' => true,
    'message' => 'Modification OK'
));