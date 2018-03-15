<?php

try {
    require_once __DIR__ . "/../includes/fonctions_inc.php";

    $code_match = filter_input(INPUT_POST, 'code_match');
    $success = acceptReport($code_match);
    echo json_encode(array(
        'success' => true,
        'message' => 'Modification OK'
    ));
} catch (phpmailerException $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Erreur durant la modification'
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Erreur durant la modification'
    ));
}


