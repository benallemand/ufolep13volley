<?php

require_once __DIR__ . "/../classes/ClubManager.php";
$manager = new ClubManager();
$inputs = filter_input_array(INPUT_POST);
try {
    $success = $manager->save($inputs);
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