<?php

require_once __DIR__ . "/../classes/CompetitionManager.php";
try {
    $competition_manager = new CompetitionManager();
    $inputs = filter_input_array(INPUT_POST);
    $competition_manager->save_friendships($inputs);
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
return;
