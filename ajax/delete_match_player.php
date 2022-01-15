<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";

$params = filter_input_array(INPUT_POST);

try {
    delete_match_player($params['id_match'], $params['id_player']);
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